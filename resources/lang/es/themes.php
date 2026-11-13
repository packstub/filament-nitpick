<?php

return [

    'appearance' => [
        'title' => 'Apariencia',
        'subheading' => 'Elige cómo se ve este panel para ti. Tu elección se guarda en tu cuenta y se aplica en todos tus dispositivos.',
        'close' => 'Cerrar',
    ],

    'picker' => [
        'label' => 'Tema',
        'helper' => 'Los cambios se aplican al instante.',
        'current' => 'Actual',
        'default' => 'Predeterminado del panel',
        'default_hint' => 'Sigue a :theme',
    ],

    'accent' => [
        'label' => 'Color de acento',
        'theme' => 'Color del tema',
        'custom' => 'Tu propio color',
        'panel' => 'Color del panel',
        'refused' => 'Ese color no se puede usar',
        'invalid' => 'Elige uno de los colores, o un color como #4f46e5.',
        'too_light' => 'Es demasiado claro para destacar en una página clara. Prueba un tono más intenso.',
        'too_dark' => 'Es demasiado oscuro para destacar en modo oscuro. Prueba un tono más brillante.',
    ],

    'manage' => [
        'everyone' => 'Para todos',
        'tenant' => 'Para :tenant',
        'description' => 'Lo que todos ven hasta que eligen un tema propio.',
        'locked_description' => 'Bloqueado: todos ven este tema y solo los administradores pueden cambiarlo.',
        'lock' => 'Bloquear para todos',
        'lock_helper' => 'Deja de lado el tema y el acento de cada persona, y cierra el selector a todos salvo a los administradores.',
    ],

    'notifications' => [
        'saved' => 'Tema actualizado',
        'unavailable' => [
            'title' => ':theme no está disponible ahora mismo',
            'body' => 'Este panel se muestra con :default en su lugar. Puedes elegir otro tema en Apariencia.',
        ],
    ],

    'themes' => [
        'aurora' => [
            'label' => 'Aurora',
            'description' => 'Superficies de cristal iridiscente y una barra lateral flotante tipo dock. Luminoso de día, profundo y sereno en modo oscuro.',
        ],
        'midnight' => [
            'label' => 'Midnight',
            'description' => 'Pensado para el modo oscuro, con acentos de violeta a cian y paneles de cristal suaves. Un aire SaaS moderno.',
        ],
    ],

];
