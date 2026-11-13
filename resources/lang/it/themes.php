<?php

return [

    'appearance' => [
        'title' => 'Aspetto',
        'subheading' => 'Scegli come appare questo pannello per te. La tua scelta viene salvata nel tuo account e vale su tutti i tuoi dispositivi.',
        'close' => 'Chiudi',
    ],

    'picker' => [
        'label' => 'Tema',
        'helper' => 'Le modifiche si applicano subito.',
        'current' => 'Attuale',
        'default' => 'Predefinito del pannello',
        'default_hint' => 'Segue :theme',
    ],

    'accent' => [
        'label' => 'Colore di accento',
        'theme' => 'Colore del tema',
        'custom' => 'Il tuo colore',
        'panel' => 'Colore del pannello',
        'refused' => 'Questo colore non si può usare',
        'invalid' => 'Scegli uno dei colori, oppure un colore come #4f46e5.',
        'too_light' => 'È troppo chiaro per risaltare su una pagina chiara. Prova una tonalità più intensa.',
        'too_dark' => 'È troppo scuro per risaltare in modalità scura. Prova una tonalità più brillante.',
    ],

    'manage' => [
        'everyone' => 'Per tutti',
        'tenant' => 'Per :tenant',
        'description' => 'Quello che tutti vedono finché non scelgono un tema proprio.',
        'locked_description' => 'Bloccato: tutti vedono questo tema e solo i gestori possono cambiarlo.',
        'lock' => 'Blocca per tutti',
        'lock_helper' => 'Mette da parte il tema e l’accento di ciascuno e chiude il selettore a tutti tranne i gestori.',
    ],

    'notifications' => [
        'saved' => 'Tema aggiornato',
        'unavailable' => [
            'title' => ':theme non è disponibile al momento',
            'body' => 'Questo pannello viene mostrato con :default. Puoi scegliere un altro tema da Aspetto.',
        ],
    ],

    'themes' => [
        'aurora' => [
            'label' => 'Aurora',
            'description' => 'Superfici di vetro iridescente e una barra laterale fluttuante in stile dock. Luminoso di giorno, profondo e calmo in modalità scura.',
        ],
        'midnight' => [
            'label' => 'Midnight',
            'description' => 'Pensato per la modalità scura, con accenti dal viola al ciano e morbidi pannelli di vetro. Un look SaaS moderno.',
        ],
    ],

];
