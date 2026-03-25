<?php

return [

	// System
	'error' => 'VIGA',
	'no-rights-for-op' => 'Sul puudub selle toimingu teostamise õigus!',
	'all-fields-are-required' => 'Kõik allolevad väljad peavad olema nõuetekohaselt täidetud',
	'all-fields' => 'Kõik väljad peavad olema korrektselt täidetud',
	'no-user-text' => '<h3 class="text-center">Tere tulemust Racster keskkonda!</h3><p class="text-center">Kasutamiseks palun võta ühendust administraatoriga!</p>',

	// General
	'add-new' => 'Lisa uus',
	'view-image' => 'Vaata pilti',
	'select' => 'Vali',
	'change' => 'Muuda',
	'save' => 'Salvesta',
	'cancel' => 'Tühista',
	'close' => 'Sulge',
	'close-window' => 'Sulge',
	'filters' => 'Filtrid',
	'filter-select-coach' => 'Treener',
	'filter-select-entry-type' => 'Formaat',
	'filter-select-location' => 'Asukoht',
	'clear-filters' => 'Eemalda filtrid',

	// Navigation
	'register' => 'Registeeri',
	'login' => 'Logi sisse',
	'back' => 'Tagasi',
	'homepage' => 'Avaleht',
	'show-timetable' => 'Tunniplaan',
	'et-lang' => 'Eesti keeles',
	'en-lang' => 'in English',
	'et-3code' => 'EST',
	'en-3code' => 'ENG',
	'your-credit' => 'Krediit: :balance',
	'your-level' => 'Tase: :level',
	'manage-products' => 'Tooted',
	'manage-assets' => 'Seadistamine',
	'manage-users' => 'Kasutajad',
	'manage-subscriptions' => 'Tellimused',
	'edit-profile' => 'Muuda profiili',
	'my-transactions' => 'Maksed',
	'my-subscriptions' => 'Püsiajad',
	'my-notifications' => 'Teavitused',
	'logout' => 'Logi välja',

	// Welcome page
	'welcome-to-racster' => 'Tere RACSTER keskkonda!',
	'go-to-dashboard' => 'Mine töölauale',
	'get-started' => 'Alusta',

	// Dashboard
	'dashboard-main-card-header' => 'Töölaud',
	'dashboard-balance-card-header' => 'Minu saldo',
	'dashboard-settings-card-header' => 'Seadistused',
	'you-are-logged-in' => 'Tere :name!',
	'your-transactions-balance' => 'Sinu tehingute saldo on <strong>:balance</strong>',
	'your-next-entry' => 'Sinu järgmine treening',
	'no-next-entry' => '<a href="'.LaravelLocalization::localizeUrl('/timetable').'">Mine tunniplaanile</a> ja pane kirja järgmisele treeningule',
	'go-to-entry' => 'Vaata treeningut',
	'go-to-timetable' => 'Mine tunniplaanile',
	'view-transactions' => 'Tehingud',
	'view-subscriptions' => 'Tellimused',
	'view-notifications' => 'Teavitused',
	'manage-profile' => 'Minu profiil',

	// Profile
	'profile-must-be-completed' => 'Palun täitke oma profiil enne jätkamist.',
	'profile-info-title' => 'Profiili info',
	'profile-info-description' => "Halda oma konto profiili infot.",
	'username-field-name' => 'Kasutajanimi',
	'email-field-name' => 'E-posti aadress',
	'your-email-is-unverified' => 'Teie e-posti aadress on kinnitamata.',
	'click-to-send-verification-email' => 'Kinnitusmeili uuesti saatmiseks klõpsake siin.',
	'new-verification-email-sent' => 'Teie e-posti aadressile on saadetud uus kinnituslink.',
	'first-name-field-name' => 'Eesnimi',
	'last-name-field-name' => 'Perekonnanimi',
	'phone-field-name' => 'Telefon',
	'phone-number-is-invalid' => 'Telefoni number ei ole korrektne.',
	'birthday-field-name' => 'Sünnikuupäev',
	'level-field-name' => 'Minu tase',
	'watch-skills-video' => 'Vaata videot oskustest',
	'profile-image-field-name' => 'Profiili pilt',
	'allowed-image-types' => 'Lubatud formaadid jpg,jpeg,png,webp ja kuni :maxmb MB',
	'i-accept-the-terms' => 'Olen nõus <a href="'.LaravelLocalization::localizeUrl("privacy").'" target="_blank">privaatsuspoliitika</a> ja <a href="'.LaravelLocalization::localizeUrl("terms").'" target="_blank">kasutustingimustega</a>',
	'join-newsletter-field-name' => 'Soovin uudiskirjaga liituda',
	'info-saved-successfully' => 'Info uuendatud edukalt.',
	'update-password-title' => 'Uuenda parooli',
	'update-password-description' => 'Turvalisuse tagamiseks veenduge, et teie konto kasutab pikka ja juhuslikku parooli.',
	'update-password-current-password' => 'Preagune parool',
	'update-password-new-password' => 'Uus parool',
	'update-password-confirm-password' => 'Kinnita parool',
	'delete-account-title' => 'Kustuta konto',
	'delete-account-description' => 'Kui teie konto on kustutatud, kustutatakse kõik selle ressursid ja andmed jäädavalt. Enne konto kustutamist laadige alla kõik andmed või teave, mida soovite säilitada.',
	'delete-account-button' => 'Kustuta konto',
	'delete-account-modal-title' => 'Kas oled kindel, et soovid oma konto kustutada?',
	'delete-account-modal-description' => 'Konto kustutamisel eemaldatakse teie profiil ja kõik sellega seotud broneeringud jäädavalt. Arved ja makseandmed säilitatakse seadusest tulenevalt kuni 7 aastat.',
	'delete-account-modal-password-field-name' => 'Parool',
	'delete-account-modal-password-field-placeholder' => 'Parool',
	'delete-account-modal-confirmation-button' => 'Jah. Kustuta minu konto',

	// Timetable - main view
	'timetable-main-greeting' => 'Tere, :name',
	'timetable-main-header' => 'Broneeri uus aeg:',
	'timetable-view-calendar' => 'Kuu',
	'timetable-view-week' => 'Nädal',
	'timetable-settings-modal-label' => 'Kalendri seaded',
	'timetable-settings-modal-title' => 'Seaded',
	'hide-my-entries' => 'Kuva ainult vabad ajad',
	'hide-free-entries' => 'Kuva ainult minu ajad',
	'show-all-entries-modal-button' => 'Eemalda filter',
	'hidden-active-entries-mine' => 'ainult vabad ajad',
	'hidden-active-entries-free' => 'ainult minu ajad',
	'week' => 'nädal',
	'show-today' => 'Tagasi',
	'monday' => 'Esmaspäev',
	'tuesday' => 'Teisipäev',
	'wednesday' => 'Kolmapäev',
	'thursday' => 'Neljapäev',
	'friday' => 'Reede',
	'saturday' => 'Laupäev',
	'sunday' => 'Pühapäev',
	'monday-short' => 'E',
	'tuesday-short' => 'T',
	'wednesday-short' => 'K',
	'thursday-short' => 'N',
	'friday-short' => 'R',
	'saturday-short' => 'L',
	'sunday-short' => 'P',
	'1-month' => 'Jaanuar',
	'2-month' => 'Veebruar',
	'3-month' => 'Märts',
	'4-month' => 'Aprill',
	'5-month' => 'Mai',
	'6-month' => 'Juuni',
	'7-month' => 'Juuli',
	'8-month' => 'August',
	'9-month' => 'September',
	'10-month' => 'Oktoober',
	'11-month' => 'November',
	'12-month' => 'Detsember',
	'1-month-short' => 'Jaan',
	'2-month-short' => 'Veebr',
	'3-month-short' => 'Märts',
	'4-month-short' => 'Apr',
	'5-month-short' => 'Mai',
	'6-month-short' => 'Juuni',
	'7-month-short' => 'Juuli',
	'8-month-short' => 'Aug',
	'9-month-short' => 'Sept',
	'10-month-short' => 'Okt',
	'11-month-short' => 'Nov',
	'12-month-short' => 'Dets',
	'hour' => 'tund',
	'hour-short' => 'H',
	'attend' => 'Osale',

	// Timetable - entry modal
	'entry-modal-label' => 'Treeningu info modaalaken',
	'entry-modal-title' => 'Treeningu info',
	'entry-info-acquired-successfully' => 'Treeningu info on edukalt omandatud',
	'entry-level' => 'Tase:',
	'for-all-levels' => 'Kõik tasemed',
	'entry-coaches-title' => 'Treener',
	'click-on-coach-for-details' => 'Loe lisaks klikates treenerile',
	'back-to-entry-info' => 'Tagasi üldinfole',
	'entry-attendees-title' => 'Osalejad',
	'other-client' => 'Teine klient',
	'participate-in-the-entry' => 'Osale',
	'no-access-due-to-participant-limit-exceeded' => 'Vabandame! Treeningule on juba registreerunud maksimaalne arv osalejaid.',
	'min-cancellation-time-has-passed' => 'Tühistamise aeg on möödas',
	'min-participation-time-has-passed' => 'Osalemise aeg on möödas',
	'prebooking-is-limited-by' => 'Eelbroneerida saab kuni 2 nädalat',
	'not-enough-credit' => 'Pole piisavalt krediiti!',
	'pay-with-stripe' => 'Stripe makse',
	'pay-for-onetime' => 'Tee makse',
	'pay-for-subscription' => 'Tee püsimakse',
	'use-credit' => 'Kasuta krediiti',
	'i-want-privately' => 'Soovin privaatselt',
	'resume-payment' => 'Jätka maksega',
	'subscribed' => 'Püsimakse',
	'sure-you-want-to-attend-the-entry' => 'Kas soovite kindlasti treeningul osaleda?',
	'sure-you-want-to-cancel-the-attendance' => 'Kas soovite kindlasti treeningul osalemise tühistada?',
	'transaction-attending-comment' => 'Treeningul osalemine',
	'transaction-cancelling-attendance-comment' => 'Treeningu õigeaegne tühistamine',
	'participant-limit-exceeded' => 'Vabandame! Osalejate piirang on juba ületatud.',
	'participation-saved-successfully' => 'Osavõtt edukalt salvestatud!',
	'participation-cancelled-successfully' => 'Osavõtt edukalt tühistatud!',
	'update-entry-button' => 'Muuda treeningut',

	// Timetable - attending entry email
	'attending-to-entry-email-subject' => ':start osalemise kinnitus',
	'attending-to-entry-email-heading' => ':title',
	'attending-to-entry-email-content' => '
<p>Oled edukalt registreerunud.</p>
<ul>
<li>Aeg: :start (:length)</li>
<li>Formaat: :format</li>
<li>Hind: :price</li>
<li>Asukoht: <a href=":maplink" target="_blank" class="racster-inline-btn racster-secondary">:location</a></li>
<li>Treener(id): :coaches</li>
</ul>
',
	'attending-to-entry-email-content-several-days' => '
<h3 style="text-align:center;">Oled edukalt registreerunud :start kuni :ending.</h3>
<p>Here is the ":title" overview: </p>
<ul>
<li>Format: :format</li>
<li>Price: :price</li>
<li>Location: <a href=":maplink" target="_blank" class="racster-inline-btn racster-secondary">:location</a></li>
<li>Coach(es): :coaches</li>
</ul>
',

	// Timetable - manage entry
	'add-new-entry' => 'Loo uus treening',
	'timetable-manage-entry-main-header' => '',
	'back-to-timetable' => 'Tagasi tunniplaanile',
	'field-entry-title' => 'Pealkiri',
	'field-entry-description' => 'Kirjeldus',
	'field-entry-description-placeholder' => 'Treeningu lühikirjeldus',
	'field-entry-type' => 'Formaat',
	'select-entry-type' => 'Vali formaat',
	'entry-type-price-acquired-successfully' => 'Treeningu tüübi info on edukalt omandatud',
	'recurring-entry' => 'Püsiaeg',
	'create-recurring-dates' => 'Loo korduvad ajad',
	'view-past-dates' => 'Vaata möödunud aegu',
	'field-client-level' => 'Tase',
	'select-client-level' => 'Vali tase',
	'for-all-client-levels' => 'Kõik tasemed',
	'field-client-limit' => 'Klientide arv',
	'various-clients' => 'Erinevad kliendid',
	'field-entry-price' => 'Ühe osaleja hind',
	'price-extra-information' => '<strong>Sisesta hind mis kehtib ühe osaleja jaoks ühes trennis, laagris või turniiril</strong>, sama ka püsiaja trenni puhul. Kui klient tühistab ühe püsiaja broneeringu, nt. 12. jaanuar trenni, siis saab klient vastava trenni summa krediidina enda kontole.',
	'extra-price' => 'Erihind',
	'field-entry-monthly-fee' => 'Ühe osaleja kuutasu',
	'field-entry-coaches' => 'Treener',
	'no-related-users-set' => 'Ühtegi treenerit pole lisatud',
	'field-entry-date' => 'Aeg',
	'field-entry-starting' => 'Algusaeg',
	'field-entry-ending' => 'Lõpuaeg',
	'field-entry-length' => 'Kestvus',
	'select-entry-length' => 'Vali kestvus',
	'minutes-short' => 'min',
	'field-entry-location' => 'Väljak',
	'select-entry-location' => 'Vali väljak',
	'field-entry-clients' => 'Kliendid',
	'search-for-clients' => 'Otsi kliente ...',
	'add-client-to-list' => 'Lisa klient',
	'sure-to-remove-the-client-from-entry-date' => 'Kas soovite kindlasti kliendi eemaldada?',
	'save-entry-to-confirm-clients' => 'Kliendi muudatuste kinnitamiseks salvestage treening',
	'private-entry' => 'Privaatne',
	'delete-date-line' => 'Kustuta rida',
	'sure-to-delete-date-line' => 'Kas soovite kindlasti rea kustutada?',
	'add-new-date-line' => 'Lisa uus aja rida',
	'save-entry-data' => 'Salvesta',
	'sure-to-save-entry-data-as-entered' => 'Kas olete kindel, et treeningu info on korrektselt sisestatud?',
	'unfortunately-this-date-has-already-been-booked' => 'Kahjuks pole mõni kuupäev või koht saadaval.',
	'entry-data-successfully-updated' => 'Treening on edukalt salvestatud!',
	'view-related-subscriptions' => 'Vaata tellimusi',

	// Timetable - recurring dates modal
	'recurring-dates-modal-label' => 'Püsiaegade loomise modaalaken',
	'recurring-dates-modal-title' => 'Loo korduvad ajad',
	'field-entry-recurring-dates-days' => 'Nädalapäevad',
	'field-entry-recurring-dates-specification' => 'Kordumine',
	'recurring-specification-weekly' => 'Iga nädal',
	'recurring-specification-over-week' => 'Üle nädala',
	'field-entry-recurring-dates-period' => 'Korduv kuni',
	'field-entry-recurring-starting' => 'Algusaeg',
	'field-entry-recurring-ending' => 'Lõpuaeg',
	'create-recurring-dates-modal-button' => 'Loo ajad',
	'sure-you-want-to-create-the-recurring-dates' => 'Kas soovite kindlasti luua korduvad ajad?',
	'recurring-dates-created-successfully' => 'Korduvad kuupäevad edukalt loodud!',

	// Timetable - past dates modal
	'past-dates-modal-label' => 'Möödunud aegade modaalaken',
	'past-dates-modal-title' => 'Möödunud ajad',
	'entry-past-dates-acquired-successfully' => 'Treeningu möödunud ajad on edukalt omandatud',

	// Timetable - entry subscriptions modal
	'subscriptions-modal-label' => 'Aktiivsete tellimuste modaalaken',
	'subscriptions-modal-title' => 'Aktiivsed tellimused',
	'entry-subscriptions-acquired-successfully' => 'Treeningu tellimused on edukalt omandatud',

	// Notifications
	'notifications-main-card-header' => 'Minu teavitused',
	'notifications-main-card-description' => '<strong>Siit saad tellida endale e-kirja teavitused uute sündmuste kohta, mida veel broneerimissüsteemis pole.</strong> Nii oled esimeste seas ja saad soovi korral kohe registreeruda.',
	'notifications-select-all' => 'Vali kõik',
	'notifications-clear-all' => 'Eemalda kõik',
	'notifications-select-coaches-title' => 'Treener',
	'notifications-select-types-title' => 'Formaat',
	'notifications-select-locations-title' => 'Asukoht',
	'notification-filters-successfully-updated' => 'Filtrid on edukalt uuendatud!',
	'notification-activation-successfully-updated' => 'Filtrite aktiivsus on edukalt uuendatud!',
	'enable-notifications' => 'Aktiveeri teavitused',
	'notifications-successfully-activated' => 'Teavitused on edukalt aktiveeritud!',
	'disable-notifications' => 'Tühista teavitused',
	'notifications-successfully-disabled' => 'Teavitused on edukalt tühistatud!',
	'available-entry-dates-notification-email-subject' => 'Racsteris on mõned kuupäevad saadaval',
	'available-entry-dates-notification-email-content' => 'Järgmised kuupäevad võivad teile huvi pakkuda:<br /><ul>:dateslist</ul>',
	'available-entry-dates-notification-email-content-date' => '<a href=":openlink" target="_blank" class="racster-inline-btn racster-link">:title @ :date</a>',

	// Subscription notifications
	'subscription-precharge-notice-email-subject' => 'Teavitus: Püsimakse toimub 3 päeva pärast',
	'subscription-precharge-notice-email-content' => '
<p>Hei!</p>
<p><strong>Sinu järgmine püsimakse tennisetrenni eest toimub 3 päeva pärast, 25. kuupäeval.</strong> Tasu võetakse automaatselt Sinu kontolt broneerimisüsteemi kaudu.</p>
<p>Kui soovid makse andmeid kontrollida või uuendada vajuta <a href="https://dashboard.stripe.com/login" target="_blank">siia</a>.
<p>Head päeva!<br />Fööniks Tenniseklubi</p>
	',
	'subscription-recurring-notice-email-subject' => 'Makse õnnestus!',
	'subscription-recurring-notice-email-content' => '
<p>Hei!</p>
<p>Sinu püsimakse tennisetrenni eest täna, 25. kuupäeval õnnestus edukalt. Tasu on võetud automaatselt Sinu kontolt.</p>
<p>Mõnusat päeva Sulle!<br />Fööniks Tenniseklubi</p>
	',
	'subscription-failed-notice-email-subject-0' => 'Hoiatus! Makse ebaõnnestus - mittetasumisel püsitrennid lõppevad!',
	'subscription-failed-notice-email-content-day-0' => '
<p>Hei!</p>
<p>Palun pane vajalik summa enda pangakontole, et homme automaatne makse õnnestuks.</p>
<p><strong>Sinu püsimakse tennisetrenni eest 25. kuupäeval ei õnnestunud, kuna kontol polnud piisavalt vahendeid.</strong></p>
<p>Vajadusel muuda makseandmeid oma kontol <a href="https://dashboard.stripe.com/login" target="_blank">SIIT</a>.</p>
<p>Oled kõigeks võimeline!<br />Fööniks Tenniseklubi</p>
	',
	'subscription-failed-notice-email-subject-1' => 'Hoiatus! Kui homme makse ei õnnestu, lõpeb trenni püsiaeg!',
	'subscription-failed-notice-email-content-day-1' => '
<p>Hei!</p>
<p>Püsimakse pole meieni tänaseni jõudnud</p>
<p>Ära jää enda püsiajast ilma - pane kindlasti TÄNA vajalik summa enda pangakontole, et homme automaatne makse õnnestuks.</p>
<p><strong><span style="color:#ff0000;">Kui homme makse ei õnnestu, lõpeb trenni püsiaeg automaatselt</span> ja Sinu trennikoht vabastatakse teistele broneerimiseks!</strong> Nii jääd Sa enda kohast paraku ilma.</p>
<p>Kui Sul on raskusi tasumisel, palun võta meiega otse ühendust, et leiaksime koos lahenduse.</p>
<p>Vajadusel muuda makseandmeid oma kontol <a href="https://dashboard.stripe.com/login" target="_blank">SIIT</a>.</p>
<p>Edu!<br />Fööniks Tenniseklubi</p>
',
	'subscription-failed-notice-email-subject-2' => 'NB! Sinu püsiaeg on tühistatud - makse ei laekunud!',
	'subscription-failed-notice-email-content-day-2' => '
<p>Hei!</p>
<p>Paraku ei saanud me õigeaegselt Sinu makset püsitrennis osalemiseks ja seetõttu vabastasime trenni koha teiste jaoks.</p>
<p>Kui soovid püsiajaga jätkata, võta meiega kiiresti kindlasti ühendust! Võimalusel leiame lahenduse ja saad enda püsitrennis osaleda.</p>
<p>Kui soovid mitte jätkata, anna endast märku, siis teame arvestada :)</p>
<p>Kõike head!<br />Fööniks Tenniseklubi</p>
',
	'sms-reminder-of-failed-payment-0' => 'Hoiatus! Tennisetrennide püsimakse ebaõnnestus. Et püsitrenn jätkuks, kanna summa kindlasti kontole, et homme makse õnnestuks! Fööniks Tenniseklubi',
	'sms-reminder-of-failed-payment-1' => 'Hoiatus! Püsimakse pole meieni jõudnud. Pane TÄNA raha kontole, et homme makse õnnestuks! Vastasel juhul lõpeb püsiaeg ja Sinu koht vabastatakse! Fööniks Tenniseklubi',
	'sms-reminder-of-failed-payment-2' => 'NB! Sinu püsiaeg on tühistatud – makse ei laekunud. Võta kiiresti ühendust, et püsiajaga jätkata! Fööniks Tenniseklubi',
	'subscription-failed-notice-email-content-day-5' => 'Makse sinu ":entryTitle" püsiaja eest ebaõnnestus :failDate. Alates homsest hakatakse arvele arvestama viivist.',
	'subscription-failed-notice-email-content-day-10' => 'Makse sinu ":entryTitle" püsiaja eest ebaõnnestus :failDate. Viivis on ... €.',
	'subscription-failed-notice-email-content-day-20' => 'Makse sinu ":entryTitle" püsiaja eest ebaõnnestus :failDate. Viivis on ... €. Kui järgmiseks makse tähtajaks ei ole makse koos viivisega laekunud, tühistatakse püsiaeg automaatselt.',
	'subscription-failed-notice-email-content-day-30' => 'Makse sinu ":entryTitle" püsiaja eest ebaõnnestus :failDate. Püsiaeg tühistatakse automaatselt.',
	'subscription-failed-admin-notice-email-subject' => 'Kliendi püsiaeg on tühistatud alates tänasest',
	'subscription-failed-admin-notice-email-content' => '
<p>Hei!</p>
<p>Kliendi püsimakse ei laekunud õigeks tähtajaks (:failDate). Süsteem saatis kliendile meeldetuletuse kirjad eri päevadel, samuti ka sms-id.</p>
<p>Viimases kliendikirjas teavitati klienti, et tema püsiaeg (":entryTitle") on peatatud ja vabastatud. Teised kliendid saavad nüüd selle ajale broneeringu teha. Ühtlasi on klient suunatud klubi administraatoriga ühendust võtma sõltumata sellest kas ta soovib või ei soovi püsiajaga jätkata.</p>
<p>Soovitus - võta ühendust kliendiga ja võimalusel leia viis, et klient saaks püsitrenniga jätkata.</p>
<p><em>See on automaatne sõnum.</em></p>
<p>Kõike head,<br />Racster</p>
',
	'subscription-notice-email-footer' => 'Kõik õigused kaitstud.',

	// Users management
    'manage-users-title' => '&nbsp;',
	'role-name' => 'Rolli nimi',
	'none' => 'Puhasta',
	'add-role' => 'Lisa roll',
	'manage-users-credit' => 'Krediit',
	'manage-users-settings' => 'Tase ja soodustus',
	'manage-users-coach' => 'Treeneri tutvustus',
	'users' => 'kasutajat',
	'change-user-data' => 'Halda infot',
	'manage-user-credit' => 'Halda krediiti',
	'manage-user-credit-title' => 'Kasutaja tehingud',
	'transaction-amount-placeholder' => 'Summa',
	'transaction-comment-placeholder' => 'Selgitus',
	'transactions' => 'tehingut',
	'add-credit-to-user' => 'Lisa krediiti',
	'sure-to-add-new-credit' => 'Kas soovite kindlasti lisada krediiti?',
	'credit-added-successfully' => 'Krediit edukalt lisatud!',
	'delete-transaction-row' => 'Kustuta tehing',
	'sure-to-delete-the-transaction' => 'Kas soovite kindlasti krediidi kustuta?',
	'user-credit-delete-successfully' => 'Krediit edukalt kustutatud!',
	'total-amount' => 'Konto saldo',
	'no-transactions-found' => 'Tehinguid ei ole tehtud!',
	'manage-user' => 'Halda kasutajat:',
	'back-to-users-list' => 'Kasutajate haldusesse',
	'field-discount' => 'Soodustus',
	'select-discount-percent' => 'Vali %',
	'coaches-description' => 'Treeneri lühikirjeldus',
	'save-user-info' => 'Salvesta kasutaja info',
	'userdata-changed-successfully' => 'Kasutajainfo edukalt uuendatud!',
	'delete-selected-users' => 'Kustuta kasutajad',
	'sure-to-delete-users' => 'Kas soovite kindlasti valitud kasutajaid kustutada?',
	'sure-to-remove-user-role' => 'Kas soovite kindlasti kasutajarolli eemaldada?',
	'new-role-successfully-added' => 'Uus roll on edukalt lisatud!',
	'role-successfully-updated' => 'Roll on edukalt uuendatud!',
	'selected-users-roles-successfully-updated' => 'Valitud kasutajate rollid on edukalt uuendatud!',
	'user-role-successfully-removed' => 'Kasutaja roll on edukalt eemaldatud!',
	'users-role-successfully-deleted' => 'Kasutaja roll on edukalt kustutatud!',

];
