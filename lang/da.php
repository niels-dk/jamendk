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
    'nav.capture'        => 'Fang',
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
];
