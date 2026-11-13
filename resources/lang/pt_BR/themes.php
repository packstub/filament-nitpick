<?php

return [

    'appearance' => [
        'title' => 'Aparência',
        'subheading' => 'Escolha como este painel aparece para você. Sua escolha fica salva na sua conta e vale em todos os seus dispositivos.',
        'close' => 'Fechar',
    ],

    'picker' => [
        'label' => 'Tema',
        'helper' => 'As mudanças valem na hora.',
        'current' => 'Atual',
        'default' => 'Padrão do painel',
        'default_hint' => 'Segue :theme',
    ],

    'accent' => [
        'label' => 'Cor de destaque',
        'theme' => 'Cor do tema',
        'custom' => 'Sua própria cor',
        'panel' => 'Cor do painel',
        'refused' => 'Essa cor não pode ser usada',
        'invalid' => 'Escolha uma das cores, ou uma cor como #4f46e5.',
        'too_light' => 'Ela é clara demais para se destacar numa página clara. Tente um tom mais forte.',
        'too_dark' => 'Ela é escura demais para se destacar no modo escuro. Tente um tom mais vivo.',
    ],

    'manage' => [
        'everyone' => 'Para todos',
        'tenant' => 'Para :tenant',
        'description' => 'O que todos veem até escolherem um tema próprio.',
        'locked_description' => 'Bloqueado: todos veem este tema, e só os administradores podem mudá-lo.',
        'lock' => 'Bloquear para todos',
        'lock_helper' => 'Deixa de lado o tema e a cor de destaque de cada pessoa, e fecha o seletor para todos exceto os administradores.',
    ],

    'notifications' => [
        'saved' => 'Tema atualizado',
        'unavailable' => [
            'title' => ':theme está indisponível no momento',
            'body' => 'Este painel está sendo mostrado com :default. Você pode escolher outro tema em Aparência.',
        ],
    ],

    'themes' => [
        'aurora' => [
            'label' => 'Aurora',
            'description' => 'Superfícies de vidro iridescente e uma barra lateral flutuante no estilo dock. Claro de dia, profundo e calmo no modo escuro.',
        ],
        'midnight' => [
            'label' => 'Midnight',
            'description' => 'Feito para o modo escuro, com destaques do violeta ao ciano e painéis de vidro suaves. Um visual SaaS moderno.',
        ],
    ],

];
