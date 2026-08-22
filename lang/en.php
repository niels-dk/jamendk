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


    /* ── Shared visibility labels ── */
    'vis.visibility'     => 'Visibility',
    'vis.show_dashboard' => 'Show on Dashboard',

    /* ── Workflow overlay ── */
    'wf.status'           => 'Status',
    'wf.notes_placeholder'=> 'Anything worth tracking — blockers, next steps, decisions…',
    'wf.show_section'     => 'Show section',

    /* ── Relations overlay ── */
    'rel.search_placeholder' => 'Search by name or paste ID…',

    /* ── Basics overlay ── */
    'basics.title'         => 'Vision Basics',
    'basics.start'         => 'Start date',
    'basics.end'           => 'End date',
    'basics.publishing'    => 'Trip publishing',
    'basics.publish'       => 'Publish as Trip',
    'basics.never'         => 'Never expires',
    'basics.exp7'          => 'Expire in 7 days',
    'basics.exp30'         => 'Expire in 30 days',
    'basics.sections_hint' => 'Choose which sections appear when this trip is published.',
    'basics.share_link'    => 'Public share link — anyone with it can view the trip',
    'basics.copy'          => 'Copy',
    'basics.new_link'      => 'New link',

    /* ── Budget overlay ── */
    'budget.currency'        => 'Currency',
    'budget.search_currency' => 'Search currency…',

    /* ── Contacts overlay ── */
    'contacts.add'            => 'Add contact',
    'contacts.add_field'      => 'Add field',
    'contacts.fields'         => 'Fields',
    'contacts.flags'          => 'Flags',
    'contacts.current'        => 'Current',
    'contacts.main'           => 'Main',
    'contacts.none_yet'       => 'No contacts yet.',
    'contacts.load_failed'    => 'Failed to load contacts.',
    'contacts.confirm_delete' => 'Delete this contact?',
    'contacts.custom'         => 'Custom…',
    'contacts.custom_prompt'  => 'Enter custom key',
    'contacts.unnamed'        => '(unnamed)',

    /* ── Roles overlay ── */
    'roles.add_collab'       => 'Add collaborator',
    'roles.email_placeholder'=> 'Their account email…',
    'roles.viewer'           => 'Viewer — read-only',
    'roles.editor'           => 'Editor — can modify content',
    'roles.co_owner'         => 'Co-owner — full control incl. sharing',
    'roles.delegate'         => 'Delegate — acts on behalf of the owner',
    'roles.owner'            => 'Owner',
    'roles.viewer_short'     => 'Viewer',
    'roles.editor_short'     => 'Editor',
    'roles.co_owner_short'   => 'Co-owner',
    'roles.delegate_short'   => 'Delegate',
    'roles.add_team'         => 'Add from your teams',
    'roles.manage_teams'     => 'Manage teams',
    'roles.no_members'       => 'No members.',
    'roles.load_failed'      => 'Failed to load members.',
    'roles.enter_email'      => 'Enter an email.',
    'roles.adding'           => 'Adding…',
    'roles.added'            => 'Added',
    'roles.add_failed'       => 'Add failed',
    'roles.unknown_email'    => "If a creator with that email exists, they'll appear in the list above.",
    'roles.cancelled'        => 'Cancelled — nobody added.',
    'roles.confirm_remove'   => 'Remove this collaborator?',
    'roles.remove_failed'    => 'Remove failed',
    'roles.update_failed'    => 'Update failed',
    'roles.whole_team'       => 'Whole team',
    'roles.already_on'       => 'already on the board',

    /* ── Goals overlay ── */
    'goals.title'             => 'Title',
    'goals.title_placeholder' => 'What needs to happen?',
    'goals.description'       => 'Description',
    'goals.desc_placeholder'  => 'Optional context, links, why this matters…',
    'goals.priority'          => 'Priority',
    'goals.due'               => 'Due date',
    'goals.assign_to'         => 'Assign to',
    'goals.unassigned'        => '— Unassigned —',
    'goals.milestones'        => 'Milestones',
    'goals.comments'          => 'Comments',
    'goals.comment_placeholder'=> 'Write a comment…',
    'goals.delete'            => 'Delete goal',
    'goals.not_started'       => 'Not started',
    'goals.in_progress'       => 'In progress',
    'goals.awaiting'          => 'Awaiting',
    'goals.done'              => 'Done',
    'goals.cancelled'         => 'Cancelled',
    'goals.none_yet'          => 'No goals yet. Add one to get started.',
    'goals.no_comments'       => 'No comments yet.',
    'goals.save_first'        => 'Save the goal first, then comment.',
    'goals.comment_failed'    => 'Comment failed',
    'goals.action_failed'     => 'Action failed',
    'goals.note_prompt'       => 'Add a note for the assigner (optional):',
    'goals.confirm_delete'    => 'Delete this goal and its milestones?',
    'goals.milestone_placeholder' => 'Milestone…',
    'goals.no_team'           => 'Not in a team',
    'goals.ms_due'            => 'Milestone due date',
    'goals.ms_assign'         => 'Assign this milestone',
    'goals.load_one_failed'   => 'Failed to load goal',


    /* ── Teams page ── */
    'teams.im_on'          => "Teams I'm on",
    'teams.new_placeholder'=> 'New team name… (e.g. Film crew)',
    'teams.add_placeholder'=> 'Add a member by account email…',
    'teams.member_email'   => "Member's account email…",
    'teams.add_member'     => 'Add member',
    'teams.rename'         => 'Rename',
    'teams.delete'         => 'Delete team',
    'teams.leave'          => 'Leave team',
    'teams.default_role'   => 'Default role',
    'teams.role_on_team'   => 'Role on this team',
    'teams.member'         => 'Member',
    'teams.last_active'    => 'Last active',
    'teams.no_members'     => 'No members yet — add someone below.',
    'teams.no_boards'      => 'No boards yet',
    'teams.owner'          => 'Owner',
    'teams.enter_name'     => 'Enter a team name first.',
    'teams.rename_prompt'  => 'New team name:',
    'teams.confirm_delete' => 'Delete this team? Board access already granted stays in place — only the group itself is removed.',
    'teams.confirm_leave'  => 'Leave this team? Any board access you already have stays.',
    'teams.enter_email'    => "Enter the member's account email.",
    'teams.confirm_remove' => 'Remove this member from the team? Their existing board access stays.',

    /* ── Vision show / form ── */
    'vision.back_dash'        => 'Back to dashboard',
    'vision.edit'             => 'Edit Vision',
    'vision.your_tasks'       => 'Your tasks on this board',
    'vision.resolve_task'     => 'Resolve task',
    'vision.send_back'        => 'Send back to owner',
    'vision.note_placeholder' => 'Optional note…',
    'vision.title_placeholder'=> 'Vision title',
    'vision.scope'            => 'Project Scope',
    'vision.choose'           => 'Choose…',
    'vision.save'             => 'Save Vision',
    'vision.save_stay'        => 'Save & stay',
    'vision.save_close'       => 'Save & close',
    'vision.save_dash'        => 'Save & dashboard',


    /* ── Anchor types (values stay English — labels only) ── */
    'anchor.locations' => 'Locations',
    'anchor.brands'    => 'Brands',
    'anchor.people'    => 'People',
    'anchor.seasons'   => 'Seasons',
    'anchor.time'      => 'Time',

    /* ── Vision form extras ── */
    'vision.name'              => 'Vision Name',
    'vision.anchors'           => 'Anchors',
    'vision.anchors_help'      => 'Quick, queryable tags like locations, brands, people, seasons/time. Helps search & dashboards.',
    'vision.anchor_placeholder'=> 'e.g. Copenhagen / Adidas / Alice / Winter / Q1',
    'vision.custom_key'        => 'Custom key',

    /* ── Workflow ── */
    'wf.complete' => 'Complete',

    /* ── Goals extras ── */
    'goals.add'           => 'Add goal',
    'goals.add_milestone' => 'Add milestone',
    'goals.send'          => 'Send',
    'goals.mark_resolved' => 'Mark resolved',
    'goals.p1'            => 'Urgent',
    'goals.p2'            => 'High',
    'goals.p3'            => 'Normal',
    'goals.p4'            => 'Low',
    'goals.p5'            => 'Lowest',

    /* ── Budget extras ── */
    'budget.line_items'      => 'Line items',
    'budget.line_items_hint' => 'travel, gear, talent…',
    'budget.add_line'        => 'Add line',
    'budget.paid'            => 'Paid?',

    /* ── Documents ── */
    'docs.upload'        => 'Upload',
    'docs.download'      => 'Download',
    'docs.none_yet'      => 'No documents yet.',
    'docs.draft'         => 'Draft',
    'docs.waiting_brand' => 'Waiting Brand',
    'docs.final'         => 'Final',
    'docs.signed'        => 'Signed',


    /* ── Dates & relative time ── */
    'date.medium'  => ':m :d, :y',
    'month.jan' => 'Jan', 'month.feb' => 'Feb', 'month.mar' => 'Mar',
    'month.apr' => 'Apr', 'month.may' => 'May', 'month.jun' => 'Jun',
    'month.jul' => 'Jul', 'month.aug' => 'Aug', 'month.sep' => 'Sep',
    'month.oct' => 'Oct', 'month.nov' => 'Nov', 'month.dec' => 'Dec',

    'time.just_now'    => 'just now',
    'time.in_a_moment' => 'in a moment',
    'time.min_ago'     => ':n min ago',
    'time.in_min'      => 'in :n min',
    'time.hr_ago'      => ':n hr ago',
    'time.in_hr'       => 'in :n hr',
    'time.yesterday'   => 'yesterday',
    'time.tomorrow'    => 'tomorrow',
    'time.today'       => 'today',
    'time.day_ago'     => '1 day ago',
    'time.days_ago'    => ':n days ago',
    'time.in_days'     => 'in :n days',

    'common.untitled'  => 'Untitled',

    /* ── Home — logged in ── */
    'home.welcome'          => 'Welcome',
    'home.welcome_sub'      => 'Start with a Dream — one line is enough. You can grow it later.',
    'home.first_dream'      => 'Start your first Dream',
    'home.example_title'    => 'Not sure where to start?',
    'home.example_body'     => "Load a complete example project — a Dream that grew into a real Vision with a shot list, budget, contacts and a published Trip page. It goes into your account, so you can click through it, change it, and delete it when you've got the idea.",
    'home.example_btn'      => 'Load example project',

    'home.step1'        => 'Catch it',
    'home.step1_short'  => 'A Dream is one line — the thought before it evaporates. No forms, no fields.',
    'home.step1_k'      => "Works with no signal. Syncs when you're back.",
    'home.step2'        => 'Grow it',
    'home.step2_short'  => 'Promote it to a Vision: dates, contacts, documents, budget, and the shot list of what you want to film.',
    'home.step2_k'      => 'Bring in your team when you need them.',
    'home.step3'        => 'Take it with you',
    'home.step3_short'  => 'Publish a Trip page — one link, no login, works offline, ready to share.',
    'home.step3_k'      => 'Or print it and use a pencil.',

    'home.welcome_back'     => 'Welcome back',
    'home.welcome_back_sub' => 'Pick up where you left off, or start something new.',
    'home.go_dashboard'     => 'Go to dashboard',
    'home.create'           => 'Create',
    'home.new_dream'        => 'New Dream',
    'home.new_vision'       => 'New Vision',
    'home.new_mood'         => 'New Mood',
    'home.your_boards'      => 'Your boards',
    'home.trip_ready'       => ':n trip ready to share',
    'home.trips_ready'      => ':n trips ready to share',
    'home.recently_updated' => 'Recently updated',
    'home.no_activity'      => 'No recent activity yet.',
    'home.updated'          => 'Updated',
    'home.upcoming'         => 'Upcoming dates',
    'home.overdue'          => 'Overdue',
    'home.ends'             => 'Ends',
    'home.starts'           => 'Starts',
    'home.goal'             => 'Goal',
    'home.milestone'        => 'Milestone',
    'home.untitled_vision'  => 'Untitled vision',

    /* ── Footer ── */
    'footer.blurb'        => "Catch the idea, grow it into a plan, and open the shot list when you're standing there. Built for filmmakers and creators.",
    'footer.instagram'    => 'Instagram',
    'footer.email'        => 'Email',
    'footer.product'      => 'Product',
    'footer.how_it_works' => 'How it works',
    'footer.support'      => 'Support',
    'footer.help'         => 'Help',
    'footer.contact'      => 'Contact',
    'footer.legal'        => 'Legal',
    'footer.privacy'      => 'Privacy policy',
    'footer.terms'        => 'Terms',
    'footer.made_in'      => 'Made in Denmark',


    'footer.tested_in'    => 'tested in Brazil',

    'common.failed'        => 'Failed',

    /* ── Vision show page ── */
    'anchor.seasons_time'          => 'Seasons / Time',
    'vision.overdue'               => 'overdue',
    'vision.sent_back_waiting'     => 'Sent back — waiting for the owner',
    'vision.resolve'               => 'Resolve',
    'vision.send_back_short'       => 'Send back',
    'vision.send_task_back'        => 'Send task back',
    'vision.note_seen_by_assigner' => 'Whoever assigned it will see your note on their next dashboard visit.',
    'vision.sending'               => 'Sending…',
    'vision.sent'                  => 'Sent',
    'vision.from_dream'            => 'From Dream:',
    'vision.under_construction'    => 'This Vision board is under construction.',
    'vision.created'               => 'Created',
    'vision.handoff_tip'           => "Tell the owner you're done — they get a note on their next visit",
    'vision.owner_sees_note'       => 'The owner will see your note the next time they open their dashboard.',
    'vision.handoff_placeholder'   => "Optional note — what did you do, what's left, anything to look at…",


    'action.remove' => 'Remove',

    /* ── Basics: trip publishing ── */
    'basics.trip_master_tip'  => 'Master switch — when off, the trip page is not available.',
    'basics.trip_master_help' => 'Master switch — when off, /trips/:slug shows "not published".',
    'basics.mint_tip'         => 'Mint a fresh link — the old one stops working',
    'basics.expires'          => 'Expires :date',
    'basics.copied'           => 'Copied',
    'basics.confirm_mint'     => 'Mint a new link? The current one stops working immediately.',

    /* ── Budget totals ── */
    'budget.total'        => 'Total',
    'budget.sum_of_lines' => '= sum of line items',
    'budget.lines'        => 'lines',
    'budget.remaining'    => 'remaining',
    'budget.over_by'      => 'OVER by',

    /* ── Contact field labels (values stay English — see overlay_contacts.php) ── */
    'contacts.f_name'    => 'Name',
    'contacts.f_company' => 'Company',
    'contacts.f_address' => 'Address',
    'contacts.f_mobile'  => 'Mobile',
    'contacts.f_email'   => 'Email',
    'contacts.f_country' => 'Country',
    'contacts.load_one_failed' => 'Failed to load contact',

    /* ── Documents ── */
    'docs.no_group'         => '— No group —',
    'docs.new_group'        => 'New group…',
    'docs.new_group_prompt' => 'New group name:',
    'docs.trip_toggle_tip'  => 'Click to toggle visibility on the Trip layer',
    'docs.on_trip'          => 'On trip',
    'docs.off_trip'         => 'Off trip',
    'docs.update_failed'    => 'Update failed',
    'docs.status_failed'    => 'Failed to update status',
    'docs.create_failed'    => 'Create failed',
    'docs.choose_file'      => 'Choose a file first.',
    'docs.uploading'        => 'Uploading…',
    'docs.uploaded'         => 'Uploaded',
    'docs.upload_failed'    => 'Upload failed',

    /* ── Goals ── */
    'goals.off_board'   => 'not on board yet',
    'goals.user'        => 'User',
    'goals.returned'    => 'Returned',
    'goals.resolved'    => 'Resolved',
    'goals.load_failed' => 'Failed to load goals.',

    /* ── Relations ── */
    'rel.one_mood_hint' => 'Only one mood board per vision. To change, remove the current one first.',

    /* ── Roles ── */
    'roles.no_name' => '(no name)',

    /* ── Shots ── */
    'shots.mark_captured'  => 'Mark captured',
    'shots.no_mood_linked' => 'No mood board linked yet — link one under Relations to pick reference images.',

];
