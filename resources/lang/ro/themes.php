<?php

return [

    'appearance' => [
        'title' => 'Aspect',
        'subheading' => 'Alege cum arată acest panou pentru tine. Alegerea ta se salvează în cont și se aplică pe toate dispozitivele tale.',
        'close' => 'Închide',
    ],

    'picker' => [
        'label' => 'Temă',
        'helper' => 'Schimbările se aplică imediat.',
        'current' => 'Actuală',
        'default' => 'Implicita panoului',
        'default_hint' => 'Urmează :theme',
    ],

    'accent' => [
        'label' => 'Culoare de accent',
        'theme' => 'Culoarea temei',
        'custom' => 'Culoarea ta',
        'panel' => 'Culoarea panoului',
        'refused' => 'Culoarea aceasta nu poate fi folosită',
        'invalid' => 'Alege una dintre culori sau o culoare precum #4f46e5.',
        'too_light' => 'E prea deschisă ca să iasă în evidență pe o pagină deschisă. Încearcă o nuanță mai intensă.',
        'too_dark' => 'E prea închisă ca să iasă în evidență în modul întunecat. Încearcă o nuanță mai luminoasă.',
    ],

    'manage' => [
        'everyone' => 'Pentru toți',
        'tenant' => 'Pentru :tenant',
        'description' => 'Ce văd toți până își aleg o temă proprie.',
        'locked_description' => 'Blocat: toți văd această temă și doar administratorii o pot schimba.',
        'lock' => 'Blochează pentru toți',
        'lock_helper' => 'Lasă deoparte tema și culoarea de accent ale fiecăruia și închide selectorul pentru toți, în afară de administratori.',
    ],

    'notifications' => [
        'saved' => 'Tema a fost actualizată',
        'unavailable' => [
            'title' => ':theme nu este disponibilă acum',
            'body' => 'Panoul este afișat în schimb cu :default. Poți alege altă temă din Aspect.',
        ],
    ],

    'themes' => [
        'aurora' => [
            'label' => 'Aurora',
            'description' => 'Suprafețe de sticlă irizată și o bară laterală plutitoare, ca un dock. Luminoasă ziua, profundă și calmă în modul întunecat.',
        ],
        'midnight' => [
            'label' => 'Midnight',
            'description' => 'Gândită pentru modul întunecat, cu accente de la violet la cyan și panouri de sticlă discrete. Un aer SaaS modern.',
        ],
    ],

];
