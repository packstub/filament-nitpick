<?php

return [

    'appearance' => [
        'title' => 'Weergave',
        'subheading' => 'Kies hoe dit paneel er voor jou uitziet. Je keuze wordt in je account bewaard en geldt op al je apparaten.',
        'close' => 'Sluiten',
    ],

    'picker' => [
        'label' => 'Thema',
        'helper' => 'Wijzigingen gelden meteen.',
        'current' => 'Huidig',
        'default' => 'Standaard van het paneel',
        'default_hint' => 'Volgt :theme',
    ],

    'accent' => [
        'label' => 'Accentkleur',
        'theme' => 'Kleur van het thema',
        'custom' => 'Eigen kleur',
        'panel' => 'Kleur van het paneel',
        'refused' => 'Die kleur kan niet worden gebruikt',
        'invalid' => 'Kies een van de kleuren, of een kleur zoals #4f46e5.',
        'too_light' => 'Ze is te licht om op te vallen op een lichte pagina. Probeer een diepere tint.',
        'too_dark' => 'Ze is te donker om op te vallen in de donkere modus. Probeer een fellere tint.',
    ],

    'manage' => [
        'everyone' => 'Voor iedereen',
        'tenant' => 'Voor :tenant',
        'description' => 'Wat iedereen ziet tot ze een eigen thema kiezen.',
        'locked_description' => 'Vergrendeld: iedereen ziet dit thema, en alleen beheerders kunnen het wijzigen.',
        'lock' => 'Vergrendelen voor iedereen',
        'lock_helper' => 'Zet ieders eigen thema en accent opzij, en sluit de kiezer voor iedereen behalve beheerders.',
    ],

    'notifications' => [
        'saved' => 'Thema bijgewerkt',
        'unavailable' => [
            'title' => ':theme is nu niet beschikbaar',
            'body' => 'Dit paneel wordt in plaats daarvan in :default getoond. Je kunt bij Weergave een ander thema kiezen.',
        ],
    ],

    'themes' => [
        'aurora' => [
            'label' => 'Aurora',
            'description' => 'Iriserende glazen vlakken en een zwevende zijbalk in dock-stijl. Licht overdag, diep en rustig in de donkere modus.',
        ],
        'midnight' => [
            'label' => 'Midnight',
            'description' => 'Gemaakt voor de donkere modus, met accenten van violet tot cyaan en zachte glazen panelen. Een modern SaaS-gevoel.',
        ],
    ],

];
