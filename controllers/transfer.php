<?php
/**
 * Transfer controller — creator-initiated account handover.
 *
 * A handover is a two-party agreement: the owner requests it, the recipient
 * accepts before anything moves. Either side can back out while it's pending
 * (owner cancels, recipient declines). Admin-driven transfers live in
 * admin_controller and skip the acceptance step.
 */
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../app/transfer.php';

class transfer_controller
{
    /** The pending outgoing request for a user, if any. */
    public static function pendingOutgoing(PDO $db, int $userId): ?array
    {
        try {
            $st = $db->prepare("SELECT at.*, u.name AS to_name, u.email AS to_email
                                  FROM account_transfers at
                                  JOIN users u ON u.id = at.to_user_id
                                 WHERE at.from_user_id = ? AND at.status = 'pending'
                                 ORDER BY at.id DESC LIMIT 1");
            $st->execute([$userId]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) { return null; }
    }

    /** Pending incoming requests for a user (shown as a dashboard banner). */
    public static function pendingIncoming(PDO $db, int $userId): array
    {
        try {
            $st = $db->prepare("SELECT at.*, u.name AS from_name, u.email AS from_email
                                  FROM account_transfers at
                                  JOIN users u ON u.id = at.from_user_id
                                 WHERE at.to_user_id = ? AND at.status = 'pending'
                                 ORDER BY at.id DESC");
            $st->execute([$userId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { return []; }
    }

    /** POST /account/transfer — owner requests a handover to another creator. */
    public static function request(): void
    {
        require_login();
        global $db, $currentUserId;
        if (!csrf_check($_POST['csrf_token'] ?? null)) { redirect('/account'); }

        $email = trim((string)($_POST['email'] ?? ''));
        $me    = (int)$currentUserId;

        $err = null;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'That email address doesn\'t look right.';
        } else {
            $to = User::findByEmail($email);
            if (!$to) {
                $err = 'No creator with that email is in the system. They need an account first.';
            } elseif ((int)$to['id'] === $me) {
                $err = 'You can\'t transfer your account to yourself.';
            } elseif (!empty($to['deactivated_at'])) {
                $err = 'That account is deactivated and can\'t receive a transfer.';
            } elseif (self::pendingOutgoing($db, $me)) {
                $err = 'You already have a transfer waiting. Cancel it first.';
            } else {
                $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 500) ?: null;
                $db->prepare("INSERT INTO account_transfers
                        (from_user_id, to_user_id, initiated_by, note)
                        VALUES (?,?, 'owner', ?)")
                   ->execute([$me, (int)$to['id'], $note]);
                $_SESSION['flash_account'] = 'Transfer requested — ' . htmlspecialchars($to['name'] ?: $email)
                    . ' will see it on their dashboard and can accept it. Nothing has moved yet.';
                redirect('/account');
            }
        }
        $_SESSION['flash_account_err'] = $err;
        redirect('/account');
    }

    /** POST /account/transfer/cancel — owner withdraws a pending request. */
    public static function cancel(): void
    {
        require_login();
        global $db, $currentUserId;
        if (!csrf_check($_POST['csrf_token'] ?? null)) { redirect('/account'); }

        $db->prepare("UPDATE account_transfers SET status='cancelled', resolved_at=NOW()
                       WHERE from_user_id=? AND status='pending'")
           ->execute([(int)$currentUserId]);
        $_SESSION['flash_account'] = 'Transfer cancelled.';
        redirect('/account');
    }

    /** Load a pending transfer addressed to the current user, or bounce. */
    private static function myIncomingOr(PDO $db, int $me, string $id): ?array
    {
        $st = $db->prepare("SELECT * FROM account_transfers
                             WHERE id=? AND to_user_id=? AND status='pending' LIMIT 1");
        $st->execute([(int)$id, $me]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** POST /transfer/{id}/accept — recipient accepts; ownership moves now. */
    public static function accept(string $id): void
    {
        require_login();
        global $db, $currentUserId;
        if (!csrf_check($_POST['csrf_token'] ?? null)) { redirect('/dashboard'); }
        $me = (int)$currentUserId;

        $t = self::myIncomingOr($db, $me, $id);
        if (!$t) { $_SESSION['flash_home'] = 'That transfer is no longer available.'; redirect('/dashboard'); }

        try {
            $moved = AccountTransfer::perform($db, (int)$t['from_user_id'], $me);
            $db->prepare("UPDATE account_transfers SET status='accepted', resolved_at=NOW() WHERE id=?")
               ->execute([(int)$t['id']]);
            // Any other pending requests to this same person from the same
            // sender are moot now.
            $db->prepare("UPDATE account_transfers SET status='cancelled', resolved_at=NOW()
                           WHERE from_user_id=? AND status='pending'")
               ->execute([(int)$t['from_user_id']]);
            $_SESSION['flash_home'] = 'Account received — you now own '
                . AccountTransfer::summaryText($moved) . '.';
        } catch (\Throwable $e) {
            $_SESSION['flash_home'] = 'Could not complete the transfer: ' . $e->getMessage();
        }
        redirect('/dashboard');
    }

    /** POST /transfer/{id}/decline — recipient declines. */
    public static function decline(string $id): void
    {
        require_login();
        global $db, $currentUserId;
        if (!csrf_check($_POST['csrf_token'] ?? null)) { redirect('/dashboard'); }

        $t = self::myIncomingOr($db, (int)$currentUserId, $id);
        if ($t) {
            $db->prepare("UPDATE account_transfers SET status='declined', resolved_at=NOW() WHERE id=?")
               ->execute([(int)$t['id']]);
            $_SESSION['flash_home'] = 'Transfer declined — nothing changed.';
        }
        redirect('/dashboard');
    }
}
