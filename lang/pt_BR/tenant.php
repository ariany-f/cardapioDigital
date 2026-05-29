<?php

return [
    'features' => [
        'motoboys_disable_blocked' => 'Não é possível desativar o módulo de entregadores enquanto houver pedido com entregador em andamento. Conclua as entregas atuais; novos pedidos não usarão o módulo após a desativação.',
    ],

    'plan_change' => [
        'page_title' => 'Plano e assinatura',
        'invalid_plan' => 'Plano indisponível para solicitação.',
        'same_plan' => 'Este já é o plano atual do restaurante.',
        'pending_exists' => 'Já existe uma solicitação pendente. Aguarde a análise da plataforma.',
        'already_reviewed' => 'Esta solicitação já foi analisada.',
        'motoboys_in_progress' => 'Não é possível migrar: há entregas com motoboy em andamento e o plano solicitado não inclui o módulo de entregadores.',
    ],
];
