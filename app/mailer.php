<?php
/**
 * Mailer — the app's single outbound-email door.
 *
 * Everything that sends mail calls Mailer::send(). The transport behind it is
 * chosen by config, so swapping providers later is a config edit, never a
 * rewrite of calling code.
 *
 * Configure in app/config.php (gitignored, server-only):
 *
 *   define('MAIL_DRIVER',    'smtp');              // 'smtp' | 'mail' | 'log'
 *   define('MAIL_HOST',      'smtp.dreamhost.com');
 *   define('MAIL_PORT',      465);                 // 465 = SSL, 587 = STARTTLS
 *   define('MAIL_USER',      'dream@jamen.dk');
 *   define('MAIL_PASS',      'your-mailbox-password');
 *   define('MAIL_FROM',      'dream@jamen.dk');    // must be a real mailbox on
 *                                                  // this domain, or DKIM/SPF fail
 *   define('MAIL_FROM_NAME', 'DreamBoard');
 *
 * With no config at all it falls back to PHP mail(), and 'log' writes to
 * mail_log without sending — handy for testing.
 *
 * No PHPMailer / composer dependency: this speaks SMTP directly, which keeps
 * the shared-hosting deploy to a plain `git pull`.
 */
class Mailer
{
    private static function cfg(string $key, $default = null)
    {
        return defined($key) ? constant($key) : $default;
    }

    /** From address; falls back to the SMTP user, then to the site host. */
    private static function fromAddress(): string
    {
        $from = self::cfg('MAIL_FROM') ?: self::cfg('MAIL_USER');
        if ($from) return $from;
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return 'noreply@' . preg_replace('/^www\./', '', $host);
    }

    /** Absolute https URL for links inside emails. */
    public static function url(string $path): string
    {
        if ($path === '' || $path[0] !== '/') $path = '/' . $path;
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = self::cfg('MAIL_SITE_HOST') ?: ($_SERVER['HTTP_HOST'] ?? 'jamen.dk');
        return $scheme . '://' . $host . $path;
    }

    /**
     * Have we already sent $max mails of this type to this address within
     * $minutes? Keeps the public forms from being turned into a mail cannon.
     * Fails open (returns false) if mail_log isn't migrated yet.
     */
    public static function rateLimited(string $email, string $type, int $max = 3, int $minutes = 60): bool
    {
        global $db;
        try {
            // $minutes is inlined, not bound: MySQL wants a literal after
            // INTERVAL, and a bound placeholder there is unreliable across
            // emulated/native prepares. Cast to int keeps it injection-safe.
            $minutes = (int)$minutes;
            $st = $db->prepare("SELECT COUNT(*) FROM mail_log
                                 WHERE to_email = ? AND type = ? AND status = 'sent'
                                   AND created_at > (NOW() - INTERVAL $minutes MINUTE)");
            $st->execute([$email, $type]);
            return (int)$st->fetchColumn() >= $max;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function log(string $to, string $subject, string $type, bool $ok, ?string $error): void
    {
        global $db;
        try {
            $db->prepare("INSERT INTO mail_log (to_email, subject, type, status, error, ip)
                          VALUES (?,?,?,?,?,?)")
               ->execute([
                   mb_substr($to, 0, 255),
                   mb_substr($subject, 0, 255),
                   $type ?: null,
                   $ok ? 'sent' : 'failed',
                   $error ? mb_substr($error, 0, 2000) : null,
                   $_SERVER['REMOTE_ADDR'] ?? null,
               ]);
        } catch (\Throwable $e) { /* table missing — sending still worked */ }
    }

    /**
     * Send one HTML email. Returns true on success.
     * Never throws: a mail failure must not take a page down with it.
     */
    public static function send(string $to, string $subject, string $html, string $type = ''): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

        $driver = self::cfg('MAIL_DRIVER', 'mail');
        $ok = false;
        $error = null;

        try {
            if ($driver === 'log') {
                $ok = true; // pretend-send, for testing
            } elseif ($driver === 'smtp') {
                $ok = self::smtpSend($to, $subject, $html, $error);
            } else {
                $ok = self::phpMailSend($to, $subject, $html);
            }
        } catch (\Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
        }

        self::log($to, $subject, $type, $ok, $error);
        return $ok;
    }

    /**
     * RFC 2047 encode a header value so non-ASCII subjects survive.
     * CR/LF are stripped first: a newline in a header value would let the
     * rest of the string be read as additional headers (injection).
     */
    private static function encodeHeader(string $s): string
    {
        $s = str_replace(["\r", "\n"], ' ', $s);
        return preg_match('/[^\x20-\x7E]/', $s)
            ? '=?UTF-8?B?' . base64_encode($s) . '?='
            : $s;
    }

    /** Build the MIME body: multipart/alternative (text + HTML). */
    private static function buildMessage(string $html, string $boundary): string
    {
        // A plain-text part markedly improves spam scores and is what
        // text-only clients show.
        $text = html_entity_decode(
            trim(preg_replace('/\n{3,}/', "\n\n",
                strip_tags(preg_replace('~<br\s*/?>|</p>|</div>|</h[1-6]>~i', "\n", $html))
            )),
            ENT_QUOTES | ENT_HTML5, 'UTF-8'
        );

        return "--{$boundary}\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n"
             . "Content-Transfer-Encoding: base64\r\n\r\n"
             . chunk_split(base64_encode($text)) . "\r\n"
             . "--{$boundary}\r\n"
             . "Content-Type: text/html; charset=UTF-8\r\n"
             . "Content-Transfer-Encoding: base64\r\n\r\n"
             . chunk_split(base64_encode($html)) . "\r\n"
             . "--{$boundary}--\r\n";
    }

    private static function headerLines(string $boundary): array
    {
        $from     = self::fromAddress();
        $fromName = self::cfg('MAIL_FROM_NAME', 'DreamBoard');
        $domain   = substr(strrchr($from, '@') ?: '@localhost', 1);

        return [
            'Date: ' . date('r'),
            'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            // Transactional mail: ask bulk scanners not to auto-reply.
            'Auto-Submitted: auto-generated',
            'X-Auto-Response-Suppress: All',
        ];
    }

    /**
     * Transport 1: PHP mail().
     *
     * The 5th argument is what makes this viable. Without it, sendmail uses
     * the shell user as the envelope sender (Return-Path:
     * dh_xxxx@iad1-shared-b7-42.dreamhost.com). Receivers check SPF against
     * THAT domain, not the From: header — so SPF passes for dreamhost.com
     * while the From: says jamen.dk. The two don't align, DMARC fails, and
     * Gmail bins it.
     *
     * Passing -f sets the envelope sender to our own domain, so SPF is
     * evaluated against jamen.dk's record (which authorises DreamHost's IPs
     * via include:netblocks.dreamhost.com) and aligns with From:. DMARC only
     * needs SPF *or* DKIM to align, so this can pass without SMTP auth.
     */
    private static function phpMailSend(string $to, string $subject, string $html): bool
    {
        $boundary = 'b' . bin2hex(random_bytes(12));
        $headers  = self::headerLines($boundary);
        $body     = self::buildMessage($html, $boundary);
        // mail() supplies its own Date/To/Subject.
        $headers  = array_values(array_filter($headers, fn($h) => stripos($h, 'Date:') !== 0));

        // -f lands on the sendmail command line, so only ever pass a value we
        // have proven is a bare email address — never raw config text.
        $from   = self::fromAddress();
        $params = filter_var($from, FILTER_VALIDATE_EMAIL) ? '-f' . $from : '';

        return @mail($to, self::encodeHeader($subject), $body,
                     implode("\r\n", $headers), $params);
    }

    /** Read one SMTP reply (handles multi-line "250-..." continuations). */
    private static function read($fp): string
    {
        $data = '';
        while (($line = fgets($fp, 700)) !== false) {
            $data .= $line;
            // Last line of a reply has a space in position 4: "250 OK"
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $data;
    }

    /**
     * Send a command and require one of $expect as the reply code.
     *
     * $sensitive marks a line whose CONTENT is a secret (the base64 username
     * and password of the AUTH LOGIN exchange). Those must never reach the
     * error string, because errors are written to mail_log — and base64 is
     * not encryption. Callers must pass it explicitly: sniffing the line for
     * "AUTH" would flag the harmless "AUTH LOGIN" command and miss the
     * password, which is sent as a bare base64 blob on the following line.
     */
    private static function cmd($fp, ?string $line, array $expect, ?string &$error,
                                bool $sensitive = false): bool
    {
        if ($line !== null) fwrite($fp, $line . "\r\n");
        $reply = self::read($fp);
        $code  = (int)substr($reply, 0, 3);
        if (in_array($code, $expect, true)) return true;
        $safeLine = ($line === null) ? '(greeting)'
                  : ($sensitive ? '[credentials]' : substr($line, 0, 100));
        $error = 'SMTP: expected ' . implode('/', $expect) . ' after "' . $safeLine
               . '", got: ' . trim($reply);
        return false;
    }

    /** Transport 2: authenticated SMTP — proper SPF/DKIM alignment. */
    private static function smtpSend(string $to, string $subject, string $html, ?string &$error): bool
    {
        $host = self::cfg('MAIL_HOST', 'localhost');
        $port = (int)self::cfg('MAIL_PORT', 587);
        $user = self::cfg('MAIL_USER', '');
        $pass = self::cfg('MAIL_PASS', '');
        $from = self::fromAddress();

        $remote = ($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'SNI_enabled'       => true,
        ]]);

        $fp = @stream_socket_client($remote, $errno, $errstr, 20,
                                    STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            $error = "SMTP connect failed ($errno): $errstr";
            return false;
        }
        stream_set_timeout($fp, 20);

        $ok = true;
        $ehlo = 'EHLO ' . (self::cfg('MAIL_SITE_HOST') ?: ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        try {
            // Greeting
            if (!self::cmd($fp, null, [220], $error)) return false;
            if (!self::cmd($fp, $ehlo, [250], $error)) return false;

            // Port 587: upgrade the plaintext connection before authenticating
            if ($port !== 465) {
                if (!self::cmd($fp, 'STARTTLS', [220], $error)) return false;
                if (!@stream_socket_enable_crypto($fp, true,
                        STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $error = 'SMTP: STARTTLS handshake failed';
                    return false;
                }
                if (!self::cmd($fp, $ehlo, [250], $error)) return false;
            }

            // AUTH LOGIN (base64 user, then base64 pass) — both marked
            // sensitive so neither can be echoed into mail_log.
            if ($user !== '') {
                if (!self::cmd($fp, 'AUTH LOGIN', [334], $error)) return false;
                if (!self::cmd($fp, base64_encode($user), [334], $error, true)) return false;
                if (!self::cmd($fp, base64_encode($pass), [235], $error, true)) {
                    // Append, never overwrite: cmd() already captured the
                    // server's own words ("535 …"), which are the only thing
                    // that says WHY it was rejected. Replacing them with a
                    // guess leaves nothing to debug from.
                    $error .= ' [auth rejected — verify MAIL_USER is the full address'
                            . ' and MAIL_PASS is the mailbox password]';
                    return false;
                }
            }

            if (!self::cmd($fp, 'MAIL FROM:<' . $from . '>', [250], $error)) return false;
            if (!self::cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251], $error)) return false;
            if (!self::cmd($fp, 'DATA', [354], $error)) return false;

            $boundary = 'b' . bin2hex(random_bytes(12));
            $headers  = self::headerLines($boundary);
            $headers[] = 'To: ' . $to;
            $headers[] = 'Subject: ' . self::encodeHeader($subject);

            $data = implode("\r\n", $headers) . "\r\n\r\n"
                  . self::buildMessage($html, $boundary);

            // Dot-stuffing: a line that is just "." would end DATA early.
            $data = preg_replace('/^\./m', '..', $data);
            fwrite($fp, $data . "\r\n.\r\n");

            if (!self::cmd($fp, null, [250], $error)) return false;
            self::cmd($fp, 'QUIT', [221], $unused);
        } catch (\Throwable $e) {
            $error = 'SMTP: ' . $e->getMessage();
            $ok = false;
        } finally {
            @fclose($fp);
        }
        return $ok && $error === null;
    }

    /* ─────────────────────────  Templates  ───────────────────────── */

    /** Shared shell so every mail looks like the same product. */
    public static function layout(string $heading, string $bodyHtml, ?string $ctaText = null, ?string $ctaUrl = null): string
    {
        $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        $cta = ($ctaText && $ctaUrl)
            ? '<p style="margin:28px 0;">
                 <a href="' . $e($ctaUrl) . '"
                    style="background:#2c5aa0;color:#ffffff;text-decoration:none;
                           padding:12px 22px;border-radius:8px;font-weight:600;
                           display:inline-block;">' . $e($ctaText) . '</a>
               </p>
               <p style="font-size:13px;color:#5a6878;margin:0 0 4px;">
                 Or paste this link into your browser:
               </p>
               <p style="font-size:12px;color:#2c5aa0;word-break:break-all;margin:0;">'
               . $e($ctaUrl) . '</p>'
            : '';

        return '<!doctype html><html><body style="margin:0;padding:0;background:#f4f5f7;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                 style="background:#f4f5f7;padding:28px 12px;">
            <tr><td align="center">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                     style="max-width:520px;background:#ffffff;border-radius:12px;
                            padding:32px;font-family:-apple-system,BlinkMacSystemFont,
                            \'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;
                            color:#2a3548;line-height:1.55;">
                <tr><td>
                  <div style="font-size:18px;font-weight:800;color:#0b1727;
                              margin-bottom:22px;">DreamBoard</div>
                  <h1 style="font-size:21px;color:#0b1727;margin:0 0 14px;">'
                    . $e($heading) . '</h1>'
                  . $bodyHtml
                  . $cta .
                  '<hr style="border:0;border-top:1px solid #e4e7ec;margin:28px 0 14px;">
                  <p style="font-size:12px;color:#8593a6;margin:0;">
                    You received this because someone used this address on '
                    . $e(self::cfg('MAIL_SITE_HOST') ?: ($_SERVER['HTTP_HOST'] ?? 'jamen.dk'))
                    . '. If that wasn\'t you, you can safely ignore this email.
                  </p>
                </td></tr>
              </table>
            </td></tr>
          </table>
        </body></html>';
    }

    public static function sendVerification(string $to, string $name, string $rawToken): bool
    {
        $url  = self::url('/verify/' . $rawToken);
        $html = self::layout(
            'Confirm your email',
            '<p style="margin:0 0 14px;">Hi ' . htmlspecialchars($name, ENT_QUOTES) . ',</p>
             <p style="margin:0;">Welcome to DreamBoard. Confirm this address and your
             account is ready — then you can start capturing dreams.</p>
             <p style="margin:14px 0 0;font-size:13px;color:#5a6878;">
             This link works once and expires in 24 hours.</p>',
            'Confirm my email', $url
        );
        return self::send($to, 'Confirm your DreamBoard email', $html, 'verify');
    }

    public static function sendPasswordReset(string $to, string $name, string $rawToken): bool
    {
        $url  = self::url('/reset/' . $rawToken);
        $html = self::layout(
            'Reset your password',
            '<p style="margin:0 0 14px;">Hi ' . htmlspecialchars($name, ENT_QUOTES) . ',</p>
             <p style="margin:0;">Use the button below to choose a new password.</p>
             <p style="margin:14px 0 0;font-size:13px;color:#5a6878;">
             This link works once and expires in 1 hour. Your current password
             stays valid until you set a new one.</p>',
            'Choose a new password', $url
        );
        return self::send($to, 'Reset your DreamBoard password', $html, 'reset');
    }

    /**
     * Sent when someone tries to register with an address that already has an
     * account. Registration shows the same "check your inbox" message either
     * way, so this mail is what makes the ambiguity honest rather than a
     * dead end for the real owner.
     */
    public static function sendAlreadyRegistered(string $to, string $name, string $rawToken): bool
    {
        $url  = self::url('/reset/' . $rawToken);
        $html = self::layout(
            'You already have an account',
            '<p style="margin:0 0 14px;">Hi ' . htmlspecialchars($name, ENT_QUOTES) . ',</p>
             <p style="margin:0;">Someone just tried to create a DreamBoard account with
             this address — but you already have one, so we didn\'t make a second.</p>
             <p style="margin:14px 0 0;">If that was you and you\'ve forgotten your
             password, you can set a new one below. Otherwise just ignore this.</p>
             <p style="margin:14px 0 0;font-size:13px;color:#5a6878;">
             This link works once and expires in 1 hour.</p>',
            'Set a new password', $url
        );
        return self::send($to, 'You already have a DreamBoard account', $html, 'reset_notice');
    }
}
