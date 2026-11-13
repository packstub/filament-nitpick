<?php

return [

    'appearance' => [
        'title' => 'Apparence',
        'subheading' => 'Choisissez l’apparence de ce panneau pour vous. Votre choix est enregistré dans votre compte et s’applique sur tous vos appareils.',
        'close' => 'Fermer',
    ],

    'picker' => [
        'label' => 'Thème',
        'helper' => 'Les changements s’appliquent tout de suite.',
        'current' => 'Actuel',
        'default' => 'Par défaut du panneau',
        'default_hint' => 'Suit :theme',
    ],

    'accent' => [
        'label' => 'Couleur d’accent',
        'theme' => 'Couleur du thème',
        'custom' => 'Votre propre couleur',
        'panel' => 'Couleur du panneau',
        'refused' => 'Cette couleur ne peut pas être utilisée',
        'invalid' => 'Choisissez l’une des couleurs, ou une couleur comme #4f46e5.',
        'too_light' => 'Elle est trop claire pour ressortir sur une page claire. Essayez une teinte plus soutenue.',
        'too_dark' => 'Elle est trop sombre pour ressortir en mode sombre. Essayez une teinte plus vive.',
    ],

    'manage' => [
        'everyone' => 'Pour tout le monde',
        'tenant' => 'Pour :tenant',
        'description' => 'Ce que tout le monde voit tant qu’il n’a pas choisi son propre thème.',
        'locked_description' => 'Verrouillé : tout le monde voit ce thème, et seuls les gestionnaires peuvent le changer.',
        'lock' => 'Verrouiller pour tout le monde',
        'lock_helper' => 'Met de côté le thème et l’accent de chacun, et réserve le sélecteur aux gestionnaires.',
    ],

    'notifications' => [
        'saved' => 'Thème mis à jour',
        'unavailable' => [
            'title' => ':theme est indisponible pour le moment',
            'body' => 'Ce panneau s’affiche avec :default à la place. Vous pouvez choisir un autre thème dans Apparence.',
        ],
    ],

    'themes' => [
        'aurora' => [
            'label' => 'Aurora',
            'description' => 'Des surfaces de verre irisé et une barre latérale flottante façon dock. Lumineux le jour, profond et calme en mode sombre.',
        ],
        'midnight' => [
            'label' => 'Midnight',
            'description' => 'Pensé pour le mode sombre, avec des accents du violet au cyan et de doux panneaux de verre. Une allure SaaS moderne.',
        ],
    ],

];
