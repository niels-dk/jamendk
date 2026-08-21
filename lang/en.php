<?php
/**
 * English — the reference language.
 *
 * Every key lives here first. Other languages fall back to this file, so a
 * missing translation degrades to English rather than to a blank space.
 *
 * Keys are grouped by surface. Placeholders are :name style.
 */
return [

    /* ── Navigation / chrome ─────────────────────────────────────── */
    'nav.capture'        => 'Capture',
    'nav.teams'          => 'Teams',
    'nav.users'          => 'Users',
    'nav.analytics'      => 'Analytics',
    'nav.links'          => 'Links',
    'nav.revenue'        => 'Revenue',
    'nav.logout'         => 'Logout',
    'nav.signin'         => 'Sign in',
    'nav.create_account' => 'Create account',
    'nav.pricing'        => 'Pricing',
    'nav.dashboard'      => 'Dashboard',
    'nav.my_account'     => 'My account',
    'nav.language'       => 'Language',
    'nav.viewing_as'     => 'Viewing as :name — Return to admin',

    /* ── Board types ─────────────────────────────────────────────── */
    'board.dreams'   => 'Dreams',
    'board.visions'  => 'Visions',
    'board.moods'    => 'Moods',
    'board.trips'    => 'Trips',
    'board.dream'    => 'Dream',
    'board.vision'   => 'Vision',
    'board.mood'     => 'Mood',
    'board.trip'     => 'Trip',

    /* ── Common actions ──────────────────────────────────────────── */
    'action.save'    => 'Save',
    'action.cancel'  => 'Cancel',
    'action.delete'  => 'Delete',
    'action.close'   => 'Close',
    'action.edit'    => 'Edit',
    'action.add'     => 'Add',
    'action.see_all' => 'See all',
    'action.loading' => 'Loading…',

    /* ── Capture screen ──────────────────────────────────────────── */
    'capture.title'       => 'Catch it',
    'capture.placeholder' => "What's the idea?\n\n“Sunrise drone over the red dunes, truck tiny in frame”",
    'capture.hint'        => 'First line becomes the title. Enter catches it — Shift+Enter for a new line.',
    'capture.button'      => 'Catch it',
    'capture.caught'      => 'Caught',
    'capture.caught_open' => 'Caught — open it or keep going',
    'capture.offline'     => "Caught — saved on this phone, syncs when you're back",
    'capture.offline_pill'=> 'offline — still catching',
    'capture.counter'     => ':n caught',
    'capture.my_dreams'   => 'My dreams',

    /* ── Dashboard ───────────────────────────────────────────────── */
    'dash.title'          => 'Where Dreams Connect',
    'dash.sort'           => 'Sort',
    'dash.show'           => 'Show',
    'dash.sort_latest'    => 'Latest edit',
    'dash.sort_newest'    => 'Newest',
    'dash.sort_favorites' => 'Favorites',
    'dash.welcome'        => 'Welcome',
    'dash.welcome_back'   => 'Welcome back',
    'dash.no_items'       => 'No :type yet.',
    'dash.no_trips'      => 'No trips ready yet.',
    'dash.no_trips_help' => 'A Trip is a shareable view generated from a Vision plus its Mood board. Open any Vision, link a Mood board in Relations, and the Vision will appear here. Use the Show on Trip layer toggles inside the Vision to choose which items publish.',
    'dash.create'        => 'Create :type',

    /* ── Auth: sign in ───────────────────────────────────────────── */
    'auth.welcome_back'      => 'Welcome back',
    'auth.email_or_username' => 'Email or username',
    'auth.email'             => 'Email',
    'auth.password'          => 'Password',
    'auth.forgot'            => 'Forgot?',
    'auth.signin'            => 'Sign in',
    'auth.new_here'          => 'New here?',
    'auth.create_creator'    => 'Create a Creator account',
    'auth.err_both'          => 'Please enter both email and password.',
    'auth.err_invalid'       => 'Invalid email or password.',
    'auth.err_expired'       => 'Your session expired. Please try again.',
    'auth.err_deactivated'   => "This account has been deactivated. Contact support if that's a mistake.",
    'auth.err_unverified'    => 'Please confirm your email address before signing in.',
    'auth.resend_link'       => 'Resend the confirmation link',

    /* ── Auth: register ──────────────────────────────────────────── */
    'auth.register_title'  => 'Create your account',
    'auth.name'            => 'Your name',
    'auth.err_name'        => 'Please tell us your name.',
    'auth.err_email'       => "That email address doesn't look right.",
    'auth.err_short_pass'  => 'Password must be at least 6 characters.',
    'auth.check_inbox'     => 'Check your inbox — we sent you a link to confirm your email address. It may take a minute to arrive.',

    /* ── Auth: password reset ────────────────────────────────────── */
    'auth.forgot_title'    => 'Forgot your password?',
    'auth.forgot_lead'     => "Enter the address you signed up with and we'll email you a link to choose a new one.",
    'auth.forgot_button'   => 'Email me a reset link',
    'auth.forgot_sent'     => 'If an account exists for that address, a reset link is on its way.',
    'auth.back_to_signin'  => 'Back to sign in',
    'auth.reset_title'     => 'Choose a new password',
    'auth.new_password'    => 'New password',
    'auth.confirm_password'=> 'Confirm new password',
    'auth.reset_button'    => 'Save new password',
    'auth.err_nomatch'     => "Passwords don't match.",
    'auth.reset_done'      => 'Password updated — you can sign in now.',
    'auth.link_expired'    => 'This link has expired',
    'auth.link_expired_lead' => 'Reset links last one hour and can only be used once — request a fresh one below.',
    'auth.send_new_link'   => 'Send a new reset link',

    /* ── Email: shared ───────────────────────────────────────────── */
    'email.hi'             => 'Hi :name,',
    'email.footer_note'    => "You received this because someone used this address on :host. If that wasn't you, you can safely ignore this email.",
    'email.paste_link'     => 'Or paste this link into your browser:',

    /* ── Email: verification ─────────────────────────────────────── */
    'email.verify.subject' => 'Confirm your Merely a Dream email',
    'email.verify.heading' => 'Confirm your email',
    'email.verify.body'    => 'Welcome to Merely a Dream. Confirm this address and your account is ready — then you can start capturing dreams.',
    'email.verify.expiry'  => 'This link works once and expires in 24 hours.',
    'email.verify.cta'     => 'Confirm my email',

    /* ── Email: password reset ───────────────────────────────────── */
    'email.reset.subject'  => 'Reset your Merely a Dream password',
    'email.reset.heading'  => 'Reset your password',
    'email.reset.body'     => 'Use the button below to choose a new password.',
    'email.reset.expiry'   => 'This link works once and expires in 1 hour. Your current password stays valid until you set a new one.',
    'email.reset.cta'      => 'Choose a new password',

    /* ── Email: already registered ───────────────────────────────── */
    'email.exists.subject' => 'You already have a Merely a Dream account',
    'email.exists.heading' => 'You already have an account',
    'email.exists.body'    => "Someone just tried to create a Merely a Dream account with this address — but you already have one, so we didn't make a second.",
    'email.exists.body2'   => "If that was you and you've forgotten your password, you can set a new one below. Otherwise just ignore this.",
    'email.exists.expiry'  => 'This link works once and expires in 1 hour.',
    'email.exists.cta'     => 'Set a new password',

    /* ── Account page ────────────────────────────────────────────── */
    'account.title'         => 'My account',
    'account.signed_in_as'  => 'Signed in as :email',
    'account.profile'       => 'Profile',
    'account.company'       => 'Company',
    'account.organisation'  => 'Organisation',
    'account.optional'      => 'optional',
    'account.save_profile'  => 'Save profile',
    'account.change_pass'   => 'Change password',
    'account.current_pass'  => 'Current password',
    'account.repeat_pass'   => 'Repeat new password',
    'account.updated'       => 'Profile updated.',
    'account.pass_changed'  => 'Password changed.',
    'account.language'      => 'Language',
    'account.language_hint' => 'Changes the interface and the emails we send you.',
];
