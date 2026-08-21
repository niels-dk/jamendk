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

    /* ── Vision sections (sidebar + overlay headings) ── */
    'sec.basics'         => 'Basics',
    'sec.relations'      => 'Relations',
    'sec.itinerary'      => 'Itinerary',
    'sec.shots'          => 'Shots',
    'sec.goals'          => 'Goals & Milestones',
    'sec.budget'         => 'Budget',
    'sec.roles'          => 'Roles & Permissions',
    'sec.contacts'       => 'Contacts',
    'sec.documents'      => 'Documents',
    'sec.workflow'       => 'Workflow',
    'sec.show_on_trip'   => 'Show on Trip layer',
    'sec.hidden_on_trip' => 'hidden on trip',

    /* ── Status / feedback ── */
    'status.saving'        => 'Saving…',
    'status.saved'         => 'Saved',
    'status.save_failed'   => 'Save failed',
    'status.net_error'     => 'Network error',
    'status.delete_failed' => 'Delete failed',

    /* ── Shots overlay ── */
    'shots.lead'              => 'Everything you want to capture. Type an idea and press Enter — sort out the day, angle and references later.',
    'shots.quick_placeholder' => 'New shot idea… e.g. Sunrise drone over the dunes',
    'shots.shot'              => 'Shot',
    'shots.title_placeholder' => 'What do you want to capture?',
    'shots.type'              => 'Type',
    'shots.light'             => 'Light',
    'shots.light_any'         => 'Any time',
    'shots.light_sunrise'     => 'Sunrise',
    'shots.light_golden'      => 'Golden hour',
    'shots.light_midday'      => 'Midday',
    'shots.light_blue'        => 'Blue hour',
    'shots.light_night'       => 'Night',
    'shots.type_drone'        => 'Drone',
    'shots.type_broll'        => 'B-roll',
    'shots.type_interview'    => 'Interview / to camera',
    'shots.type_timelapse'    => 'Timelapse',
    'shots.type_photo'        => 'Photo',
    'shots.type_pov'          => 'POV / action',
    'shots.type_other'        => 'Other',
    'shots.day'               => 'Day',
    'shots.day_hint'          => 'empty = anytime',
    'shots.location'          => 'Location',
    'shots.location_placeholder' => 'Place or address',
    'shots.how'               => 'How',
    'shots.how_hint'          => 'angle, movement, what to say…',
    'shots.how_placeholder'   => 'Low pass south to north, end on the car. Remember to mention the sponsor.',
    'shots.refs'              => 'Reference images',
    'shots.refs_hint'         => 'from the linked mood board — tap to pin',
    'shots.must'              => 'Must-have',
    'shots.captured'          => 'Captured',
    'shots.reopen'            => 'Reopen',
    'shots.anytime'           => 'Anytime — keep an eye out',
    'shots.none_yet'          => 'No shots yet — add the first idea above.',
    'shots.progress'          => ':done of :total captured',
    'shots.progress_must'     => 'must-haves :done/:total',
    'shots.load_failed'       => 'Failed to load shots.',
    'shots.confirm_delete'    => 'Delete this shot?',
    'shots.migration'         => 'Run db/migrations/2026-07-14_shots.sql first.',

    /* ── Itinerary overlay ── */
    'itin.lead'                => 'The day-by-day plan. Entries marked "Show on Trip layer" appear at the top of the published trip page.',
    'itin.add_entry'           => 'Add entry',
    'itin.date'                => 'Date',
    'itin.time'                => 'Time',
    'itin.what'                => 'What',
    'itin.what_placeholder'    => 'e.g. Drive to the dunes, sunrise shoot…',
    'itin.location_hint'       => 'optional — becomes a map link',
    'itin.location_placeholder'=> 'Address or place name',
    'itin.notes'               => 'Notes',
    'itin.notes_placeholder'   => 'Gear to bring, who to call, backup plan…',
    'itin.delete_entry'        => 'Delete entry',
    'itin.none_yet'            => 'No entries yet — build the day-by-day plan.',
    'itin.load_failed'         => 'Failed to load itinerary.',
    'itin.confirm_delete'      => 'Delete this itinerary entry?',
    'itin.migration'           => 'Run db/migrations/2026-07-13_itinerary_budget_items.sql first.',

    /* ── Auth extras ── */
    'auth.min_chars'        => 'Minimum 6 characters.',
    'auth.have_account'     => 'Already have an account?',
    'auth.verify_title'     => 'Confirm your email',
    'auth.verify_lead'      => "Confirmation links last 24 hours and work once. Enter your address and we'll send a fresh one.",
    'auth.send_new_verify'  => 'Send a new link',

];
