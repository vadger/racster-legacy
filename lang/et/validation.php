<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

	'accepted' => ':attribute väli peab olema aktsepteeritud.',
	'accepted_if' => ':attribute väli peab olema aktsepteeritud, kui :other on :value.',
	'active_url' => ':attribute väli peab olema kehtiv URL.',
	'after' => ':attribute väli peab olema kuupäev pärast kuupäeva :date.',
	'after_or_equal' => ':attribute väli peab olema kuupäev pärast või võrdne kuupäevaga :date.',
	'alpha' => ':attribute väli tohib sisaldada ainult tähti.',
	'alpha_dash' => ':attribute väli tohib sisaldada ainult tähti, numbreid, sidekriipse ja alakriipse.',
	'alpha_num' => ':attribute väli tohib sisaldada ainult tähti ja numbreid.',
	'any_of' => ':attribute väli on vigane.',
	'array' => ':attribute väli peab olema massiiv.',
	'ascii' => ':attribute väli tohib sisaldada ainult ühebaidilisi tähtnumbrilisi märke ja sümboleid.',
	'before' => ':attribute väli peab olema kuupäev enne kuupäeva :date.',
	'before_today' => ':attribute väli peab olema enne tänast kuupäeva.',
	'before_or_equal' => ':attribute väli peab olema kuupäev enne või võrdne kuupäevaga :date.',
	'between' => [
		'array' => ':attribute väli peab sisaldama :min kuni :max elementi.',
		'file' => ':attribute väli peab olema vahemikus :min kuni :max kilobaiti.',
		'numeric' => ':attribute väli peab olema vahemikus :min kuni :max.',
		'string' => ':attribute väli peab olema :min kuni :max tähemärki pikk.',
	],
	'boolean' => ':attribute väli peab olema tõene või väär.',
	'can' => ':attribute väli sisaldab lubamatut väärtust.',
	'confirmed' => ':attribute välja kinnitus ei ühti.',
	'contains' => ':attribute väljal puudub nõutud väärtus.',
	'current_password' => 'Parool on vale.',
	'date' => ':attribute väli peab olema kehtiv kuupäev.',
	'date_equals' => ':attribute väli peab olema kuupäev, mis on võrdne kuupäevaga :date.',
	'date_format' => ':attribute väli peab vastama vormingule :format.',
	'decimal' => ':attribute väljal peab olema :decimal kümnendkohta.',
	'declined' => ':attribute väli peab olema tagasi lükatud.',
	'declined_if' => ':attribute väli peab olema tagasi lükatud, kui :other on :value.',
	'different' => ':attribute ja :other väljad peavad olema erinevad.',
	'digits' => ':attribute väli peab olema :digits-kohaline.',
	'digits_between' => ':attribute väli peab olema :min kuni :max-kohaline.',
	'dimensions' => ':attribute väljal on sobimatud pildi mõõtmed.',
	'distinct' => ':attribute väljal on duplikaatväärtus.',
	'doesnt_end_with' => ':attribute väli ei tohi lõppeda ühega järgmistest: :values.',
	'doesnt_start_with' => ':attribute väli ei tohi alata ühega järgmistest: :values.',
	'email' => ':attribute väli peab olema kehtiv e-posti aadress.',
	'ends_with' => ':attribute väli peab lõppema ühega järgmistest: :values.',
	'enum' => 'Valitud :attribute on vigane.',
	'exists' => 'Valitud :attribute on vigane.',
	'extensions' => ':attribute väljal peab olema üks järgmistest laienditest: :values.',
	'file' => ':attribute väli peab olema fail.',
	'filled' => ':attribute väljal peab olema väärtus.',
	'gt' => [
		'array' => ':attribute väli peab sisaldama rohkem kui :value elementi.',
		'file' => ':attribute väli peab olema suurem kui :value kilobaiti.',
		'numeric' => ':attribute väli peab olema suurem kui :value.',
		'string' => ':attribute väli peab olema pikem kui :value tähemärki.',
	],
	'gte' => [
		'array' => ':attribute väli peab sisaldama :value või rohkem elementi.',
		'file' => ':attribute väli peab olema suurem või võrdne :value kilobaidiga.',
		'numeric' => ':attribute väli peab olema suurem või võrdne :value-ga.',
		'string' => ':attribute väli peab olema pikem või võrdne :value tähemärgiga.',
	],
	'hex_color' => ':attribute väli peab olema kehtiv heksadetsimaalne värv.',
	'image' => ':attribute väli peab olema pilt.',
	'in' => 'Valitud :attribute on vigane.',
	'in_array' => ':attribute väli peab eksisteerima väljas :other.',
	'in_array_keys' => ':attribute väli peab sisaldama vähemalt ühte järgmistest võtmetest: :values.',
	'integer' => ':attribute väli peab olema täisarv.',
	'ip' => ':attribute väli peab olema kehtiv IP-aadress.',
	'ipv4' => ':attribute väli peab olema kehtiv IPv4-aadress.',
	'ipv6' => ':attribute väli peab olema kehtiv IPv6-aadress.',
	'json' => ':attribute väli peab olema kehtiv JSON-string.',
	'list' => ':attribute väli peab olema loend.',
	'lowercase' => ':attribute väli peab olema väiketähtedes.',
	'lt' => [
		'array' => ':attribute väli peab sisaldama vähem kui :value elementi.',
		'file' => ':attribute väli peab olema väiksem kui :value kilobaiti.',
		'numeric' => ':attribute väli peab olema väiksem kui :value.',
		'string' => ':attribute väli peab olema lühem kui :value tähemärki.',
	],
	'lte' => [
		'array' => ':attribute väli ei tohi sisaldada rohkem kui :value elementi.',
		'file' => ':attribute väli peab olema väiksem või võrdne :value kilobaidiga.',
		'numeric' => ':attribute väli peab olema väiksem või võrdne :value-ga.',
		'string' => ':attribute väli peab olema lühem või võrdne :value tähemärgiga.',
	],
	'mac_address' => ':attribute väli peab olema kehtiv MAC-aadress.',
	'max' => [
		'array' => ':attribute väli ei tohi sisaldada rohkem kui :max elementi.',
		'file' => ':attribute väli ei tohi olla suurem kui :max kilobaiti.',
		'numeric' => ':attribute väli ei tohi olla suurem kui :max.',
		'string' => ':attribute väli ei tohi olla pikem kui :max tähemärki.',
	],
	'max_digits' => ':attribute väli ei tohi sisaldada rohkem kui :max numbrit.',
	'mimes' => ':attribute väli peab olema faili tüübiga: :values.',
	'mimetypes' => ':attribute väli peab olema faili tüübiga: :values.',
	'min' => [
		'array' => ':attribute väli peab sisaldama vähemalt :min elementi.',
		'file' => ':attribute väli peab olema vähemalt :min kilobaiti.',
		'numeric' => ':attribute väli peab olema vähemalt :min.',
		'string' => ':attribute väli peab olema vähemalt :min tähemärki pikk.',
	],
	'min_digits' => ':attribute väli peab sisaldama vähemalt :min numbrit.',
	'missing' => ':attribute väli peab puuduma.',
	'missing_if' => ':attribute väli peab puuduma, kui :other on :value.',
	'missing_unless' => ':attribute väli peab puuduma, välja arvatud juhul kui :other on :value.',
	'missing_with' => ':attribute väli peab puuduma, kui :values on olemas.',
	'missing_with_all' => ':attribute väli peab puuduma, kui :values on olemas.',
	'multiple_of' => ':attribute väli peab olema :value kordne.',
	'not_in' => 'Valitud :attribute on vigane.',
	'not_regex' => ':attribute välja vorming on vigane.',
	'numeric' => ':attribute väli peab olema number.',
	'password' => [
		'letters' => ':attribute väli peab sisaldama vähemalt ühte tähte.',
		'mixed' => ':attribute väli peab sisaldama vähemalt ühte suurtähte ja ühte väiketähte.',
		'numbers' => ':attribute väli peab sisaldama vähemalt ühte numbrit.',
		'symbols' => ':attribute väli peab sisaldama vähemalt ühte sümbolit.',
		'uncompromised' => 'Antud :attribute on esinenud andmelekkes. Palun vali teine :attribute.',
	],
	'present' => ':attribute väli peab olema olemas.',
	'present_if' => ':attribute väli peab olema olemas, kui :other on :value.',
	'present_unless' => ':attribute väli peab olema olemas, välja arvatud juhul kui :other on :value.',
	'present_with' => ':attribute väli peab olema olemas, kui :values on olemas.',
	'present_with_all' => ':attribute väli peab olema olemas, kui :values on olemas.',
	'prohibited' => ':attribute väli on keelatud.',
	'prohibited_if' => ':attribute väli on keelatud, kui :other on :value.',
	'prohibited_if_accepted' => ':attribute väli on keelatud, kui :other on aktsepteeritud.',
	'prohibited_if_declined' => ':attribute väli on keelatud, kui :other on tagasi lükatud.',
	'prohibited_unless' => ':attribute väli on keelatud, välja arvatud juhul kui :other on :values hulgas.',
	'prohibits' => ':attribute väli keelab välja :other olemasolu.',
	'regex' => ':attribute välja vorming on vigane.',
	'required' => ':attribute väli on kohustuslik.',
	'required_array_keys' => ':attribute väli peab sisaldama kirjeid: :values.',
	'required_if' => ':attribute väli on kohustuslik, kui :other on :value.',
	'required_if_accepted' => ':attribute väli on kohustuslik, kui :other on aktsepteeritud.',
	'required_if_declined' => ':attribute väli on kohustuslik, kui :other on tagasi lükatud.',
	'required_unless' => ':attribute väli on kohustuslik, välja arvatud juhul kui :other on :values hulgas.',
	'required_with' => ':attribute väli on kohustuslik, kui :values on olemas.',
	'required_with_all' => ':attribute väli on kohustuslik, kui :values on olemas.',
	'required_without' => ':attribute väli on kohustuslik, kui :values ei ole olemas.',
	'required_without_all' => ':attribute väli on kohustuslik, kui ükski :values ei ole olemas.',
	'same' => ':attribute väli peab ühtima väljaga :other.',
	'size' => [
		'array' => ':attribute väli peab sisaldama :size elementi.',
		'file' => ':attribute väli peab olema :size kilobaiti.',
		'numeric' => ':attribute väli peab olema :size.',
		'string' => ':attribute väli peab olema :size tähemärki pikk.',
	],
	'starts_with' => ':attribute väli peab algama ühega järgmistest: :values.',
	'string' => ':attribute väli peab olema string.',
	'timezone' => ':attribute väli peab olema kehtiv ajavöönd.',
	'unique' => ':attribute on juba kasutusel.',
	'uploaded' => ':attribute üleslaadimine ebaõnnestus.',
	'uppercase' => ':attribute väli peab olema suurtähtedes.',
	'url' => ':attribute väli peab olema kehtiv URL.',
	'ulid' => ':attribute väli peab olema kehtiv ULID.',
	'uuid' => ':attribute väli peab olema kehtiv UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        'email' => [
			'unique' => 'Registreerimine ebaõnnestus. Juhul kui sul on konto juba olemas, logi sisse või taasta parool.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
		'first_name' => 'Eesnimi',
		'last_name' => 'Perekonnanimi',
		'mobile_number' => 'Telefon',
		'birthday' => 'Sünnipäev',
		'profile_image' => 'Profiili pilt',
		'terms_accepted' => 'Privaatsuspoliitika',
		'current_password' => 'Preagune parool',
		'password' => 'Uus parool',
	],

];
