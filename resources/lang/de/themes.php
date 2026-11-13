<?php

return [

    'appearance' => [
        'title' => 'Darstellung',
        'subheading' => 'Lege fest, wie dieses Panel für dich aussieht. Deine Wahl wird in deinem Konto gespeichert und gilt auf all deinen Geräten.',
        'close' => 'Schließen',
    ],

    'picker' => [
        'label' => 'Theme',
        'helper' => 'Änderungen gelten sofort.',
        'current' => 'Aktuell',
        'default' => 'Panel-Standard',
        'default_hint' => 'Folgt :theme',
    ],

    'accent' => [
        'label' => 'Akzentfarbe',
        'theme' => 'Farbe des Themes',
        'custom' => 'Eigene Farbe',
        'panel' => 'Farbe des Panels',
        'refused' => 'Diese Farbe kann nicht verwendet werden',
        'invalid' => 'Wähle eine der Farben oder eine Farbe wie #4f46e5.',
        'too_light' => 'Sie ist zu hell, um sich auf einer hellen Seite abzuheben. Versuche einen kräftigeren Ton.',
        'too_dark' => 'Sie ist zu dunkel, um sich im dunklen Modus abzuheben. Versuche einen helleren Ton.',
    ],

    'manage' => [
        'everyone' => 'Für alle',
        'tenant' => 'Für :tenant',
        'description' => 'Das sehen alle, bis sie ein eigenes Theme wählen.',
        'locked_description' => 'Gesperrt: Alle sehen dieses Theme, und nur Verwalter können es ändern.',
        'lock' => 'Für alle sperren',
        'lock_helper' => 'Setzt das eigene Theme und die Akzentfarbe aller außer Kraft und schließt die Auswahl für alle außer Verwaltern.',
    ],

    'notifications' => [
        'saved' => 'Theme aktualisiert',
        'unavailable' => [
            'title' => ':theme ist gerade nicht verfügbar',
            'body' => 'Dieses Panel wird stattdessen in :default angezeigt. Unter Darstellung kannst du ein anderes Theme wählen.',
        ],
    ],

    'themes' => [
        'aurora' => [
            'label' => 'Aurora',
            'description' => 'Schillernde Glasflächen und eine schwebende Seitenleiste im Dock-Stil. Hell am Tag, tief und ruhig im dunklen Modus.',
        ],
        'midnight' => [
            'label' => 'Midnight',
            'description' => 'Für den dunklen Modus gemacht, mit Akzenten von Violett bis Cyan und sanften Glasflächen. Ein modernes SaaS-Gefühl.',
        ],
    ],

];
