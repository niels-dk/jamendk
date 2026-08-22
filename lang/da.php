<?php
/**
 * Dansk / Danish.
 *
 * TRANSLATOR'S NOTE — please read before "fixing" these:
 *
 * Danish film professionals work in a heavily English-loaned vocabulary.
 * "Shot", "moodboard", "location", "budget", "team" and "trip" are the words
 * actually used on a Danish set — translating them to "optagelse",
 * "stemningstavle" or "lokation" reads as stiff and slightly wrong to the
 * people we're talking to. So industry terms are deliberately left in English
 * while ordinary interface language (Save, Cancel, Password) is Danish.
 *
 * Product nouns (Dream / Vision / Mood / Trip) are treated as product names.
 * "Dream" → "Drøm" works and is warm, so it's translated. "Trip" is kept
 * because it names a specific artefact in the product.
 *
 * Anything here that sounds wrong to a Dane: change it. That's the point of
 * shipping Danish first.
 */
return [

    /* ── Navigation / chrome ─────────────────────────────────────── */
    'nav.capture'        => 'Drøm',
    'nav.teams'          => 'Teams',
    'nav.users'          => 'Brugere',
    'nav.analytics'      => 'Statistik',
    'nav.links'          => 'Links',
    'nav.revenue'        => 'Omsætning',
    'nav.logout'         => 'Log ud',
    'nav.signin'         => 'Log ind',
    'nav.create_account' => 'Opret konto',
    'nav.pricing'        => 'Priser',
    'nav.dashboard'      => 'Oversigt',
    'nav.my_account'     => 'Min konto',
    'nav.language'       => 'Sprog',
    'nav.viewing_as'     => 'Ser som :name — tilbage til admin',

    /* ── Board types ─────────────────────────────────────────────── */
    'board.dreams'   => 'Drømme',
    'board.visions'  => 'Visioner',
    'board.moods'    => 'Moodboards',
    'board.trips'    => 'Trips',
    'board.dream'    => 'Drøm',
    'board.vision'   => 'Vision',
    'board.mood'     => 'Moodboard',
    'board.trip'     => 'Trip',

    /* ── Common actions ──────────────────────────────────────────── */
    'action.save'    => 'Gem',
    'action.cancel'  => 'Annullér',
    'action.delete'  => 'Slet',
    'action.close'   => 'Luk',
    'action.edit'    => 'Redigér',
    'action.add'     => 'Tilføj',
    'action.see_all' => 'Se alle',
    'action.loading' => 'Henter…',

    /* ── Capture screen ──────────────────────────────────────────── */
    'capture.title'       => 'Fang den',
    'capture.placeholder' => "Hvad er idéen?\n\n“Drone i solopgang over de røde klitter, bilen lillebitte i billedet”",
    'capture.hint'        => 'Første linje bliver titlen. Enter fanger den — Shift+Enter for ny linje.',
    'capture.button'      => 'Fang den',
    'capture.caught'      => 'Fanget',
    'capture.caught_open' => 'Fanget — åbn den eller fortsæt',
    'capture.offline'     => 'Fanget — gemt på denne telefon, synkroniserer når du er online igen',
    'capture.offline_pill'=> 'offline — fanger stadig',
    'capture.counter'     => ':n fanget',
    'capture.my_dreams'   => 'Mine drømme',

    /* ── Dashboard ───────────────────────────────────────────────── */
    'dash.title'          => 'Hvor drømme forbindes',
    'dash.sort'           => 'Sortér',
    'dash.show'           => 'Vis',
    'dash.sort_latest'    => 'Senest redigeret',
    'dash.sort_newest'    => 'Nyeste',
    'dash.sort_favorites' => 'Favoritter',
    'dash.welcome'        => 'Velkommen',
    'dash.welcome_back'   => 'Velkommen tilbage',
    'dash.no_items'       => 'Ingen :type endnu.',
    'dash.no_trips'      => 'Ingen trips klar endnu.',
    'dash.no_trips_help' => 'Et Trip er en delbar visning, der laves ud fra en Vision plus dens moodboard. Åbn en Vision, tilknyt et moodboard under Relationer, så dukker visionen op her. Brug "Vis på Trip-laget" inde i visionen til at vælge, hvad der offentliggøres.',
    'dash.create'        => 'Opret :type',

    /* ── Auth: sign in ───────────────────────────────────────────── */
    'auth.welcome_back'      => 'Velkommen tilbage',
    'auth.email_or_username' => 'E-mail eller brugernavn',
    'auth.email'             => 'E-mail',
    'auth.password'          => 'Adgangskode',
    'auth.forgot'            => 'Glemt?',
    'auth.signin'            => 'Log ind',
    'auth.new_here'          => 'Ny her?',
    'auth.create_creator'    => 'Opret en Creator-konto',
    'auth.err_both'          => 'Udfyld både e-mail og adgangskode.',
    'auth.err_invalid'       => 'Forkert e-mail eller adgangskode.',
    'auth.err_expired'       => 'Din session udløb. Prøv igen.',
    'auth.err_deactivated'   => 'Denne konto er deaktiveret. Kontakt os hvis det er en fejl.',
    'auth.err_unverified'    => 'Bekræft din e-mailadresse før du logger ind.',
    'auth.resend_link'       => 'Send bekræftelseslinket igen',

    /* ── Auth: register ──────────────────────────────────────────── */
    'auth.register_title'  => 'Opret din konto',
    'auth.name'            => 'Dit navn',
    'auth.err_name'        => 'Skriv lige dit navn.',
    'auth.err_email'       => 'Den e-mailadresse ser ikke rigtig ud.',
    'auth.err_short_pass'  => 'Adgangskoden skal være mindst 6 tegn.',
    'auth.check_inbox'     => 'Tjek din indbakke — vi har sendt dig et link til at bekræfte din e-mailadresse. Der kan gå et minut.',

    /* ── Auth: password reset ────────────────────────────────────── */
    'auth.forgot_title'    => 'Glemt din adgangskode?',
    'auth.forgot_lead'     => 'Skriv den adresse du oprettede dig med, så sender vi et link til at vælge en ny.',
    'auth.forgot_button'   => 'Send mig et nulstillingslink',
    'auth.forgot_sent'     => 'Hvis der findes en konto med den adresse, er et link på vej.',
    'auth.back_to_signin'  => 'Tilbage til login',
    'auth.reset_title'     => 'Vælg en ny adgangskode',
    'auth.new_password'    => 'Ny adgangskode',
    'auth.confirm_password'=> 'Gentag ny adgangskode',
    'auth.reset_button'    => 'Gem ny adgangskode',
    'auth.err_nomatch'     => 'Adgangskoderne er ikke ens.',
    'auth.reset_done'      => 'Adgangskoden er opdateret — du kan logge ind nu.',
    'auth.link_expired'    => 'Dette link er udløbet',
    'auth.link_expired_lead' => 'Nulstillingslinks holder én time og kan kun bruges én gang — bed om et nyt herunder.',
    'auth.send_new_link'   => 'Send et nyt nulstillingslink',

    /* ── Email: shared ───────────────────────────────────────────── */
    'email.hi'             => 'Hej :name,',
    'email.footer_note'    => 'Du får denne mail fordi nogen har brugt denne adresse på :host. Var det ikke dig, kan du roligt ignorere den.',
    'email.paste_link'     => 'Eller indsæt dette link i din browser:',

    /* ── Email: verification ─────────────────────────────────────── */
    'email.verify.subject' => 'Bekræft din e-mail til Merely a Dream',
    'email.verify.heading' => 'Bekræft din e-mail',
    'email.verify.body'    => 'Velkommen til Merely a Dream. Bekræft denne adresse, så er din konto klar — og så kan du begynde at fange drømme.',
    'email.verify.expiry'  => 'Linket virker én gang og udløber om 24 timer.',
    'email.verify.cta'     => 'Bekræft min e-mail',

    /* ── Email: password reset ───────────────────────────────────── */
    'email.reset.subject'  => 'Nulstil din adgangskode til Merely a Dream',
    'email.reset.heading'  => 'Nulstil din adgangskode',
    'email.reset.body'     => 'Brug knappen herunder til at vælge en ny adgangskode.',
    'email.reset.expiry'   => 'Linket virker én gang og udløber om 1 time. Din nuværende adgangskode gælder indtil du vælger en ny.',
    'email.reset.cta'      => 'Vælg en ny adgangskode',

    /* ── Email: already registered ───────────────────────────────── */
    'email.exists.subject' => 'Du har allerede en konto hos Merely a Dream',
    'email.exists.heading' => 'Du har allerede en konto',
    'email.exists.body'    => 'Nogen har lige prøvet at oprette en Merely a Dream-konto med denne adresse — men du har allerede én, så vi oprettede ikke en til.',
    'email.exists.body2'   => 'Var det dig, og har du glemt din adgangskode, kan du vælge en ny herunder. Ellers kan du bare ignorere denne mail.',
    'email.exists.expiry'  => 'Linket virker én gang og udløber om 1 time.',
    'email.exists.cta'     => 'Vælg en ny adgangskode',

    /* ── Account page ────────────────────────────────────────────── */
    'account.title'         => 'Min konto',
    'account.signed_in_as'  => 'Logget ind som :email',
    'account.profile'       => 'Profil',
    'account.company'       => 'Firma',
    'account.organisation'  => 'Organisation',
    'account.optional'      => 'valgfri',
    'account.save_profile'  => 'Gem profil',
    'account.change_pass'   => 'Skift adgangskode',
    'account.current_pass'  => 'Nuværende adgangskode',
    'account.repeat_pass'   => 'Gentag ny adgangskode',
    'account.updated'       => 'Profilen er opdateret.',
    'account.pass_changed'  => 'Adgangskoden er skiftet.',
    'account.language'      => 'Sprog',
    'account.language_hint' => 'Ændrer både brugerfladen og de mails vi sender dig.',

    /* -- Vision sections (sidebar + overlay headings) -- */
    'sec.basics'         => 'Basis',
    'sec.relations'      => 'Relationer',
    'sec.itinerary'      => 'Køreplan',
    'sec.shots'          => 'Shots',
    'sec.goals'          => 'Mål & milepæle',
    'sec.budget'         => 'Budget',
    'sec.roles'          => 'Roller & rettigheder',
    'sec.contacts'       => 'Kontakter',
    'sec.documents'      => 'Dokumenter',
    'sec.workflow'       => 'Workflow',
    'sec.show_on_trip'   => 'Vis på Trip-laget',
    'sec.hidden_on_trip' => 'skjult på trip',

    /* -- Status / feedback -- */
    'status.saving'        => 'Gemmer…',
    'status.saved'         => 'Gemt',
    'status.save_failed'   => 'Kunne ikke gemme',
    'status.net_error'     => 'Netværksfejl',
    'status.delete_failed' => 'Kunne ikke slette',

    /* -- Shots overlay -- */
    'shots.lead'              => 'Alt det du vil fange. Skriv en idé og tryk Enter — dag, vinkel og referencer kan du tage bagefter.',
    'shots.quick_placeholder' => 'Ny shot-idé… fx drone i solopgang over klitterne',
    'shots.shot'              => 'Shot',
    'shots.title_placeholder' => 'Hvad vil du fange?',
    'shots.type'              => 'Type',
    'shots.light'             => 'Lys',
    'shots.light_any'         => 'Når som helst',
    'shots.light_sunrise'     => 'Solopgang',
    'shots.light_golden'      => 'Golden hour',
    'shots.light_midday'      => 'Midt på dagen',
    'shots.light_blue'        => 'Blue hour',
    'shots.light_night'       => 'Nat',
    'shots.type_drone'        => 'Drone',
    'shots.type_broll'        => 'B-roll',
    'shots.type_interview'    => 'Interview / til kamera',
    'shots.type_timelapse'    => 'Timelapse',
    'shots.type_photo'        => 'Foto',
    'shots.type_pov'          => 'POV / action',
    'shots.type_other'        => 'Andet',
    'shots.day'               => 'Dag',
    'shots.day_hint'          => 'tom = når som helst',
    'shots.location'          => 'Location',
    'shots.location_placeholder' => 'Sted eller adresse',
    'shots.how'               => 'Hvordan',
    'shots.how_hint'          => 'vinkel, bevægelse, hvad du siger…',
    'shots.how_placeholder'   => 'Lavt træk fra syd mod nord, slut på bilen. Husk at nævne sponsoren.',
    'shots.refs'              => 'Referencebilleder',
    'shots.refs_hint'         => 'fra det tilknyttede moodboard — tryk for at fastgøre',
    'shots.must'              => 'Skal have',
    'shots.captured'          => 'Fanget',
    'shots.reopen'            => 'Åbn igen',
    'shots.anytime'           => 'Når som helst — hold øje',
    'shots.none_yet'          => 'Ingen shots endnu — tilføj den første idé ovenfor.',
    'shots.progress'          => ':done ud af :total fanget',
    'shots.progress_must'     => 'skal-haves :done/:total',
    'shots.load_failed'       => 'Kunne ikke hente shots.',
    'shots.confirm_delete'    => 'Slet dette shot?',
    'shots.migration'         => 'Kør db/migrations/2026-07-14_shots.sql først.',

    /* -- Itinerary overlay -- */
    'itin.lead'                => 'Dag-for-dag-planen. Punkter markeret "Vis på Trip-laget" vises øverst på den udgivne trip-side.',
    'itin.add_entry'           => 'Tilføj punkt',
    'itin.date'                => 'Dato',
    'itin.time'                => 'Tidspunkt',
    'itin.what'                => 'Hvad',
    'itin.what_placeholder'    => 'fx kør til klitterne, optagelse i solopgang…',
    'itin.location_hint'       => 'valgfri — bliver til et kortlink',
    'itin.location_placeholder'=> 'Adresse eller stednavn',
    'itin.notes'               => 'Noter',
    'itin.notes_placeholder'   => 'Grej der skal med, hvem der skal ringes til, plan B…',
    'itin.delete_entry'        => 'Slet punkt',
    'itin.none_yet'            => 'Ingen punkter endnu — byg dag-for-dag-planen.',
    'itin.load_failed'         => 'Kunne ikke hente køreplanen.',
    'itin.confirm_delete'      => 'Slet dette punkt i køreplanen?',
    'itin.migration'           => 'Kør db/migrations/2026-07-13_itinerary_budget_items.sql først.',

    /* -- Auth extras -- */
    'auth.min_chars'        => 'Mindst 6 tegn.',
    'auth.have_account'     => 'Har du allerede en konto?',
    'auth.verify_title'     => 'Bekræft din e-mail',
    'auth.verify_lead'      => 'Bekræftelseslinks holder 24 timer og virker én gang. Skriv din adresse, så sender vi et nyt.',
    'auth.send_new_verify'  => 'Send et nyt link',


    /* ── Shared visibility labels ── */
    'vis.visibility'     => 'Synlighed',
    'vis.show_dashboard' => 'Vis på oversigten',

    /* ── Workflow overlay ── */
    'wf.status'           => 'Status',
    'wf.notes_placeholder'=> 'Alt der er værd at holde styr på — blokeringer, næste skridt, beslutninger…',
    'wf.show_section'     => 'Vis sektion',

    /* ── Relations overlay ── */
    'rel.search_placeholder' => 'Søg på navn eller indsæt ID…',

    /* ── Basics overlay ── */
    'basics.title'         => 'Vision-basis',
    'basics.start'         => 'Startdato',
    'basics.end'           => 'Slutdato',
    'basics.publishing'    => 'Udgiv som Trip',
    'basics.publish'       => 'Udgiv som Trip',
    'basics.never'         => 'Udløber aldrig',
    'basics.exp7'          => 'Udløber om 7 dage',
    'basics.exp30'         => 'Udløber om 30 dage',
    'basics.sections_hint' => 'Vælg hvilke sektioner der vises når dette trip er udgivet.',
    'basics.share_link'    => 'Offentligt delelink — alle med linket kan se trippet',
    'basics.copy'          => 'Kopiér',
    'basics.new_link'      => 'Nyt link',

    /* ── Budget overlay ── */
    'budget.currency'        => 'Valuta',
    'budget.search_currency' => 'Søg valuta…',

    /* ── Contacts overlay ── */
    'contacts.add'            => 'Tilføj kontakt',
    'contacts.add_field'      => 'Tilføj felt',
    'contacts.fields'         => 'Felter',
    'contacts.flags'          => 'Markeringer',
    'contacts.current'        => 'Aktuel',
    'contacts.main'           => 'Primær',
    'contacts.none_yet'       => 'Ingen kontakter endnu.',
    'contacts.load_failed'    => 'Kunne ikke hente kontakter.',
    'contacts.confirm_delete' => 'Slet denne kontakt?',
    'contacts.custom'         => 'Eget felt…',
    'contacts.custom_prompt'  => 'Skriv navnet på feltet',
    'contacts.unnamed'        => '(uden navn)',

    /* ── Roles overlay ── */
    'roles.add_collab'       => 'Tilføj samarbejdspartner',
    'roles.email_placeholder'=> 'Deres konto-e-mail…',
    'roles.viewer'           => 'Læser — kan kun se',
    'roles.editor'           => 'Redigerer — kan ændre indhold',
    'roles.co_owner'         => 'Med-ejer — fuld kontrol inkl. deling',
    'roles.delegate'         => 'Stedfortræder — handler på ejerens vegne',
    'roles.owner'            => 'Ejer',
    'roles.viewer_short'     => 'Læser',
    'roles.editor_short'     => 'Redigerer',
    'roles.co_owner_short'   => 'Med-ejer',
    'roles.delegate_short'   => 'Stedfortræder',
    'roles.add_team'         => 'Tilføj fra dine teams',
    'roles.manage_teams'     => 'Administrér teams',
    'roles.no_members'       => 'Ingen medlemmer.',
    'roles.load_failed'      => 'Kunne ikke hente medlemmer.',
    'roles.enter_email'      => 'Skriv en e-mail.',
    'roles.adding'           => 'Tilføjer…',
    'roles.added'            => 'Tilføjet',
    'roles.add_failed'       => 'Kunne ikke tilføje',
    'roles.unknown_email'    => 'Hvis der findes en creator med den e-mail, dukker vedkommende op i listen ovenfor.',
    'roles.cancelled'        => 'Annulleret — ingen blev tilføjet.',
    'roles.confirm_remove'   => 'Fjern denne samarbejdspartner?',
    'roles.remove_failed'    => 'Kunne ikke fjerne',
    'roles.update_failed'    => 'Kunne ikke opdatere',
    'roles.whole_team'       => 'Hele teamet',
    'roles.already_on'       => 'allerede på boardet',

    /* ── Goals overlay ── */
    'goals.title'             => 'Titel',
    'goals.title_placeholder' => 'Hvad skal der ske?',
    'goals.description'       => 'Beskrivelse',
    'goals.desc_placeholder'  => 'Valgfri kontekst, links, hvorfor det betyder noget…',
    'goals.priority'          => 'Prioritet',
    'goals.due'               => 'Deadline',
    'goals.assign_to'         => 'Tildel til',
    'goals.unassigned'        => '— Ikke tildelt —',
    'goals.milestones'        => 'Milepæle',
    'goals.comments'          => 'Kommentarer',
    'goals.comment_placeholder'=> 'Skriv en kommentar…',
    'goals.delete'            => 'Slet mål',
    'goals.not_started'       => 'Ikke startet',
    'goals.in_progress'       => 'I gang',
    'goals.awaiting'          => 'Afventer',
    'goals.done'              => 'Færdig',
    'goals.cancelled'         => 'Annulleret',
    'goals.none_yet'          => 'Ingen mål endnu. Tilføj et for at komme i gang.',
    'goals.no_comments'       => 'Ingen kommentarer endnu.',
    'goals.save_first'        => 'Gem målet først, og kommentér derefter.',
    'goals.comment_failed'    => 'Kunne ikke kommentere',
    'goals.action_failed'     => 'Handlingen mislykkedes',
    'goals.note_prompt'       => 'Tilføj en note til den der tildelte (valgfri):',
    'goals.confirm_delete'    => 'Slet dette mål og dets milepæle?',
    'goals.milestone_placeholder' => 'Milepæl…',
    'goals.no_team'           => 'Ikke i et team',
    'goals.ms_due'            => 'Deadline for milepæl',
    'goals.ms_assign'         => 'Tildel denne milepæl',
    'goals.load_one_failed'   => 'Kunne ikke hente målet',


    /* ── Teams page ── */
    'teams.im_on'          => 'Teams jeg er på',
    'teams.new_placeholder'=> 'Nyt teamnavn… (fx Filmhold)',
    'teams.add_placeholder'=> 'Tilføj et medlem med konto-e-mail…',
    'teams.member_email'   => 'Medlemmets konto-e-mail…',
    'teams.add_member'     => 'Tilføj medlem',
    'teams.rename'         => 'Omdøb',
    'teams.delete'         => 'Slet team',
    'teams.leave'          => 'Forlad team',
    'teams.default_role'   => 'Standardrolle',
    'teams.role_on_team'   => 'Rolle på dette team',
    'teams.member'         => 'Medlem',
    'teams.last_active'    => 'Sidst aktiv',
    'teams.no_members'     => 'Ingen medlemmer endnu — tilføj en herunder.',
    'teams.no_boards'      => 'Ingen boards endnu',
    'teams.owner'          => 'Ejer',
    'teams.enter_name'     => 'Skriv først et teamnavn.',
    'teams.rename_prompt'  => 'Nyt teamnavn:',
    'teams.confirm_delete' => 'Slet dette team? Adgang der allerede er givet til boards bliver — kun selve gruppen fjernes.',
    'teams.confirm_leave'  => 'Forlad dette team? Den board-adgang du allerede har, bevares.',
    'teams.enter_email'    => 'Skriv medlemmets konto-e-mail.',
    'teams.confirm_remove' => 'Fjern dette medlem fra teamet? Deres nuværende board-adgang bevares.',

    /* ── Vision show / form ── */
    'vision.back_dash'        => 'Tilbage til oversigten',
    'vision.edit'             => 'Redigér vision',
    'vision.your_tasks'       => 'Dine opgaver på dette board',
    'vision.resolve_task'     => 'Afslut opgave',
    'vision.send_back'        => 'Send tilbage til ejeren',
    'vision.note_placeholder' => 'Valgfri note…',
    'vision.title_placeholder'=> 'Visionens titel',
    'vision.scope'            => 'Projektets omfang',
    'vision.choose'           => 'Vælg…',
    'vision.save'             => 'Gem vision',
    'vision.save_stay'        => 'Gem & bliv',
    'vision.save_close'       => 'Gem & luk',
    'vision.save_dash'        => 'Gem & oversigt',


    /* ── Anchor types (values stay English — labels only) ── */
    'anchor.locations' => 'Locations',
    'anchor.brands'    => 'Brands',
    'anchor.people'    => 'Personer',
    'anchor.seasons'   => 'Sæsoner',
    'anchor.time'      => 'Tid',

    /* ── Vision form extras ── */
    'vision.name'              => 'Visionens navn',
    'vision.anchors'           => 'Anchors',
    'vision.anchors_help'      => 'Hurtige, søgbare tags som locations, brands, personer, sæson/tid. Hjælper søgning og oversigter.',
    'vision.anchor_placeholder'=> 'fx København / Adidas / Alice / Vinter / Q1',
    'vision.custom_key'        => 'Eget feltnavn',

    /* ── Workflow ── */
    'wf.complete' => 'Færdig',

    /* ── Goals extras ── */
    'goals.add'           => 'Tilføj mål',
    'goals.add_milestone' => 'Tilføj milepæl',
    'goals.send'          => 'Send',
    'goals.mark_resolved' => 'Markér som løst',
    'goals.p1'            => 'Haster',
    'goals.p2'            => 'Høj',
    'goals.p3'            => 'Normal',
    'goals.p4'            => 'Lav',
    'goals.p5'            => 'Lavest',

    /* ── Budget extras ── */
    'budget.line_items'      => 'Poster',
    'budget.line_items_hint' => 'rejse, grej, medvirkende…',
    'budget.add_line'        => 'Tilføj post',
    'budget.paid'            => 'Betalt?',

    /* ── Documents ── */
    'docs.upload'        => 'Upload',
    'docs.download'      => 'Download',
    'docs.none_yet'      => 'Ingen dokumenter endnu.',
    'docs.draft'         => 'Kladde',
    'docs.waiting_brand' => 'Afventer brand',
    'docs.final'         => 'Endelig',
    'docs.signed'        => 'Underskrevet',


    /* ── Dates & relative time ── */
    'date.medium'  => ':d. :m :y',
    'month.jan' => 'jan.', 'month.feb' => 'feb.', 'month.mar' => 'mar.',
    'month.apr' => 'apr.', 'month.may' => 'maj',  'month.jun' => 'juni',
    'month.jul' => 'juli', 'month.aug' => 'aug.', 'month.sep' => 'sep.',
    'month.oct' => 'okt.', 'month.nov' => 'nov.', 'month.dec' => 'dec.',

    'time.just_now'    => 'lige nu',
    'time.in_a_moment' => 'om et øjeblik',
    'time.min_ago'     => 'for :n min siden',
    'time.in_min'      => 'om :n min',
    'time.hr_ago'      => 'for :n t siden',
    'time.in_hr'       => 'om :n t',
    'time.yesterday'   => 'i går',
    'time.tomorrow'    => 'i morgen',
    'time.today'       => 'i dag',
    'time.day_ago'     => 'for 1 dag siden',
    'time.days_ago'    => 'for :n dage siden',
    'time.in_days'     => 'om :n dage',

    'common.untitled'  => 'Uden titel',

    /* ── Home — logged in ── */
    'home.welcome'          => 'Velkommen',
    'home.welcome_sub'      => 'Start med en Dream — én linje er nok. Du kan udbygge den senere.',
    'home.first_dream'      => 'Start din første Dream',
    'home.example_title'    => 'Ved du ikke, hvor du skal starte?',
    'home.example_body'     => 'Indlæs et komplet eksempelprojekt — en Dream, der voksede til en rigtig Vision med shot-liste, budget, kontakter og en udgivet Trip-side. Det lander på din konto, så du kan klikke rundt i det, ændre det og slette det, når du har fanget idéen.',
    'home.example_btn'      => 'Indlæs eksempelprojekt',

    'home.step1'        => 'Fang den',
    'home.step1_short'  => 'En Dream er én linje — tanken, før den fordamper. Ingen formularer, ingen felter.',
    'home.step1_k'      => 'Virker uden signal. Synkroniserer, når du er tilbage.',
    'home.step2'        => 'Udbyg den',
    'home.step2_short'  => 'Gør den til en Vision: datoer, kontakter, dokumenter, budget og shot-listen over det, du vil filme.',
    'home.step2_k'      => 'Tag dit team med, når du har brug for dem.',
    'home.step3'        => 'Tag den med',
    'home.step3_short'  => 'Udgiv en Trip-side — ét link, ingen login, virker offline, klar til at dele.',
    'home.step3_k'      => 'Eller print den ud og brug en blyant.',

    'home.welcome_back'     => 'Velkommen tilbage',
    'home.welcome_back_sub' => 'Fortsæt hvor du slap, eller start noget nyt.',
    'home.go_dashboard'     => 'Gå til oversigten',
    'home.create'           => 'Opret',
    'home.new_dream'        => 'Ny Dream',
    'home.new_vision'       => 'Ny Vision',
    'home.new_mood'         => 'Nyt Mood',
    'home.your_boards'      => 'Dine boards',
    'home.trip_ready'       => ':n trip klar til at dele',
    'home.trips_ready'      => ':n trips klar til at dele',
    'home.recently_updated' => 'Senest opdateret',
    'home.no_activity'      => 'Ingen aktivitet endnu.',
    'home.updated'          => 'Opdateret',
    'home.upcoming'         => 'Kommende datoer',
    'home.overdue'          => 'Overskredet',
    'home.ends'             => 'Slutter',
    'home.starts'           => 'Starter',
    'home.goal'             => 'Mål',
    'home.milestone'        => 'Milepæl',
    'home.untitled_vision'  => 'Vision uden titel',

    /* ── Footer ── */
    'footer.blurb'        => 'Fang idéen, udbyg den til en plan, og åbn shot-listen, når du står der. Bygget til filmfolk og kreative.',
    'footer.instagram'    => 'Instagram',
    'footer.email'        => 'E-mail',
    'footer.product'      => 'Produkt',
    'footer.how_it_works' => 'Sådan virker det',
    'footer.support'      => 'Support',
    'footer.help'         => 'Hjælp',
    'footer.contact'      => 'Kontakt',
    'footer.legal'        => 'Juridisk',
    'footer.privacy'      => 'Privatlivspolitik',
    'footer.terms'        => 'Betingelser',
    'footer.made_in'      => 'Lavet i Danmark',


    'footer.tested_in'    => 'testet i Brasilien',

    'common.failed'        => 'Mislykkedes',
    'common.network_error' => 'Netværksfejl',

    /* ── Vision show page ── */
    'anchor.seasons_time'          => 'Sæsoner / Tid',
    'vision.overdue'               => 'overskredet',
    'vision.sent_back_waiting'     => 'Sendt tilbage — venter på ejeren',
    'vision.resolve'               => 'Afslut',
    'vision.send_back_short'       => 'Send tilbage',
    'vision.send_task_back'        => 'Send opgave tilbage',
    'vision.note_seen_by_assigner' => 'Den, der tildelte opgaven, ser din note næste gang de åbner oversigten.',
    'vision.sending'               => 'Sender…',
    'vision.sent'                  => 'Sendt',
    'vision.from_dream'            => 'Fra Dream:',
    'vision.under_construction'    => 'Dette Vision-board er under opbygning.',
    'vision.created'               => 'Oprettet',
    'vision.handoff_tip'           => 'Fortæl ejeren at du er færdig — de får en note næste gang de kigger forbi',
    'vision.owner_sees_note'       => 'Ejeren ser din note næste gang de åbner oversigten.',
    'vision.handoff_placeholder'   => 'Valgfri note — hvad lavede du, hvad mangler, noget der skal kigges på…',

];
