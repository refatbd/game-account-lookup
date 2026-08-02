<?php

declare(strict_types=1);

// Generated, data-driven default registry. Applications may override or extend it.
return [
    '8ballpool' => [
        'code' => '8ballpool',
        'label' => '8 Ball Pool',
        'aliases' => [
            'eightballpool',
            '8bp',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 272564,
                'price' => '14000.0000',
                'voucherTypeName' => 'EIGHT_BALL_POOL',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'aethergazer' => [
        'code' => 'aethergazer',
        'label' => 'Aether Gazer',
        'aliases' => [
            'aether',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 2,
                'price' => '16650.0',
                'voucherTypeName' => '547-AETHER_GAZER',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'voucherTypeId' => 524,
                'gvtId' => 691,
                'lvtId' => 11840,
                'pcId' => 906,
            ],
        ],
    ],
    'aov' => [
        'code' => 'aov',
        'label' => 'Arena of Valor',
        'aliases' => [
            'arenaofvalor',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'gopaygames' => [
                'code' => 'AOV',
            ],
            'codashop' => [
                'vppId' => 270294,
                'price' => '10000.0000',
                'voucherTypeName' => 'AOV',
                'nicknamePaths' => [
                    'confirmationFields.roles.0.role',
                    'confirmationFields.username',
                ],
                'serverPath' => 'confirmationFields.roles.0.server',
            ],
        ],
    ],
    'asphalt9' => [
        'code' => 'asphalt9',
        'label' => 'Asphalt 9: Legends',
        'aliases' => [
            'gamelefta9',
            'a9',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 114548,
                'price' => '479700.0',
                'voucherTypeName' => 'GAMELOFT_A9',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
    ],
    'au2mobile' => [
        'code' => 'au2mobile',
        'label' => 'AU2 Mobile',
        'aliases' => [
            'autwomobile',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'dancingidol' => [
                'enabled' => false,
                'endpoint' => 'http://dancingidol.uniuhk.com/api/role/info?roleId={playerId}',
                'allowInsecureHttp' => false,
                'nicknamePaths' => [
                    'data.rolename',
                ],
            ],
        ],
        'notes' => 'Codashop Cambodia provides the active direct-ID route. The historical Dancing Idol/UniuHK adapter remains disabled.',
    ],
    'autochess' => [
        'code' => 'autochess',
        'label' => 'Auto Chess',
        'aliases' => [],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 203879,
                'price' => '150000.0000',
                'voucherTypeName' => 'AUTO_CHESS',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'azurlane' => [
        'code' => 'azurlane',
        'label' => 'Azur Lane',
        'aliases' => [
            'azur',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 99665,
                'price' => '70000.0000',
                'voucherTypeName' => 'AZUR_LANE',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
        'servers' => [
            'avrora' => '1',
            'lexington' => '2',
            'sandy' => '3',
            'washington' => '4',
            'amagi' => '5',
            'littleenterprise' => '6',
        ],
    ],
    'badlanders' => [
        'code' => 'badlanders',
        'label' => 'Badlanders',
        'aliases' => [],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 333121,
                'price' => '2300.0000',
                'voucherTypeName' => 'BAD_LANDERS',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
        'servers' => [
            'global' => '11001',
            'jf' => '21004',
        ],
    ],
    'barbarq' => [
        'code' => 'barbarq',
        'label' => 'BarbarQ',
        'aliases' => [],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 5145,
                'price' => '120000.0000',
                'voucherTypeName' => 'ELECSOUL',
                'nicknamePaths' => [
                    'confirmationFields.apiResult',
                    'confirmationFields.username',
                ],
            ],
        ],
    ],
    'basketrio' => [
        'code' => 'basketrio',
        'label' => 'Basketrio',
        'aliases' => [],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 147203,
                'price' => '832500.0000',
                'voucherTypeName' => 'BASKETRIO',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
        'servers' => [
            'buzzerbeater' => '2',
            '001' => '3',
            '002' => '4',
        ],
    ],
    'cod' => [
        'code' => 'cod',
        'label' => 'Call of Duty Mobile',
        'aliases' => [
            'codm',
            'callofduty',
            'callofdutymobile',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'gopaygames' => [
                'code' => 'CALL_OF_DUTY',
            ],
            'codashop' => [
                'vppId' => 270251,
                'price' => '20000.0000',
                'voucherTypeName' => 'CALL_OF_DUTY',
                'nicknamePaths' => [
                    'confirmationFields.roles.0.role',
                    'confirmationFields.username',
                ],
            ],
        ],
    ],
    'captaintsubasa' => [
        'code' => 'captaintsubasa',
        'label' => 'Captain Tsubasa: Dream Team',
        'aliases' => [
            'tsubasa',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 352113,
                'price' => '1099000.0',
                'voucherTypeName' => 'CAPTAIN_TSUBASA',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'crisisaction' => [
        'code' => 'crisisaction',
        'label' => 'Crisis Action',
        'aliases' => [
            'caherogames',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 3745,
                'price' => '300000.0',
                'voucherTypeName' => 'HEROGAMES',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
    ],
    'dragoncity' => [
        'code' => 'dragoncity',
        'label' => 'Dragon City',
        'aliases' => [],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 254206,
                'price' => '65000.0000',
                'voucherTypeName' => 'DRAGON_CITY',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'dragonraja' => [
        'code' => 'dragonraja',
        'label' => 'Dragon Raja',
        'aliases' => [
            'zulongdragonraja',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 75648,
                'price' => '1000000.0',
                'voucherTypeName' => 'ZULONG_DRAGON_RAJA',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'eosred' => [
        'code' => 'eosred',
        'label' => 'EOS RED',
        'aliases' => [],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 182235,
                'price' => '852139.0',
                'voucherTypeName' => 'EOS_RED',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
    ],
    'farlight84' => [
        'code' => 'farlight84',
        'label' => 'Farlight 84',
        'aliases' => [
            'farlight',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop_dynamic' => [
                'pageSlug' => 'farlight-84',
                'storefronts' => [
                    [
                        'name' => 'malaysia-en',
                        'localePath' => 'en-my',
                    ],
                    [
                        'name' => 'hong-kong-en',
                        'localePath' => 'en-hk',
                    ],
                ],
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                    'confirmationFields.apiResult',
                ],
            ],
            'codashop' => [
                'enabled' => false,
            ],
        ],
        'notes' => 'Uses runtime Codashop storefront metadata discovery; no short-lived SKU token is committed.',
    ],
    'footballmaster2' => [
        'code' => 'footballmaster2',
        'label' => 'Football Master 2',
        'aliases' => [
            'footballmaster',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 185403,
                'price' => '1000000.0',
                'voucherTypeName' => 'FOOTBALL_MASTER',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'freefire' => [
        'code' => 'freefire',
        'label' => 'Free Fire',
        'aliases' => [
            'ff',
            'garena',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'garena' => [
                'appId' => 100067,
                'appServerId' => 0,
                'endpoint' => 'https://shop2game.com/api/auth/player_id_login',
                'referer' => 'https://shop2game.com/app/100067/idlogin',
            ],
            'codashop_browser' => [
                'pageUrl' => 'https://www.codashop.com/en-my/free-fire',
                'timeout' => 120,
                'debugPort' => 9223,
            ],
            'codashop_dynamic' => [
                'pageSlug' => 'free-fire',
                'voucherTypeName' => 'FREEFIRE',
                'storefronts' => [
                    [
                        'name' => 'malaysia-en',
                        'localePath' => 'en-my',
                    ],
                    [
                        'name' => 'malaysia-ms',
                        'localePath' => 'ms-my',
                    ],
                    [
                        'name' => 'philippines-en',
                        'localePath' => 'en-ph',
                        'pageSlug' => 'garena-free-fire-ph',
                    ],
                    [
                        'name' => 'indonesia-id',
                        'localePath' => 'id-id',
                    ],
                ],
                'nicknamePaths' => [
                    'confirmationFields.roles.0.role',
                    'confirmationFields.username',
                    'confirmationFields.nickname',
                ],
            ],
            'gopaygames' => [
                'code' => 'FREEFIRE',
            ],
            'codashop' => [
                'name' => 'legacy-indonesia',
                'pageUrl' => 'https://www.codashop.com/id-id/free-fire',
                'vppId' => 270288,
                'price' => '200000.0000',
                'voucherTypeName' => 'FREEFIRE',
                'nicknamePaths' => [
                    'confirmationFields.roles.0.role',
                    'confirmationFields.username',
                ],
            ],
        ],
        'notes' => 'Garena Shop2Game runs first and can return nickname plus country/region. Other PHP-only providers follow before the optional browser-assisted provider. DataDome or unavailable browser automation is treated as a normal provider failure.',
    ],
    'freefiremax' => [
        'code' => 'freefiremax',
        'label' => 'Free Fire MAX',
        'aliases' => [
            'ffmax',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'gopaygames' => [
                'code' => 'FREEFIRE',
            ],
        ],
    ],
    'genshinimpact' => [
        'code' => 'genshinimpact',
        'label' => 'Genshin Impact',
        'aliases' => [
            'genshin',
            'gi',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'gopaygames' => [
                'code' => 'GENSHIN_IMPACT',
            ],
            'codashop' => [
                'vppId' => 338498,
                'price' => '16500.0000',
                'voucherTypeName' => 'GENSHIN_IMPACT',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
        'servers' => [
            'osusa' => 'os_usa',
            'oseuro' => 'os_euro',
            'osasia' => 'os_asia',
            'oscht' => 'os_cht',
        ],
    ],
    'growtopia' => [
        'code' => 'growtopia',
        'label' => 'Growtopia',
        'aliases' => [
            'gt',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop_dynamic' => [
                'pageUrl' => 'https://www.codashop.com/id-id/growtopia',
                'productPath' => '/id/growtopia',
                'skuId' => 'rt_grope_gem_fountain',
                'paymentChannelId' => 220,
                'voucherTypeName' => 'GROWTOPIA',
                'voucherTypeId' => 307,
                'lvtId' => 5445,
                'gvtId' => 424,
                'vppId' => 4,
                'price' => '83876.0',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'hago' => [
        'code' => 'hago',
        'label' => 'Hago',
        'aliases' => [],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 272113,
                'price' => '29700.0000',
                'voucherTypeName' => 'HAGO',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'honkaistarrail' => [
        'code' => 'honkaistarrail',
        'label' => 'Honkai: Star Rail',
        'aliases' => [
            'hsr',
            'starrail',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 762498,
                'price' => '16500.0000',
                'voucherTypeName' => 'HONKAI_STAR_RAIL',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
        'servers' => [
            'osusa' => 'prod_official_usa',
            'oseuro' => 'prod_official_eur',
            'osasia' => 'prod_official_asia',
            'oscht' => 'prod_official_cht',
        ],
    ],
    'honkaiimpact3' => [
        'code' => 'honkaiimpact3',
        'label' => 'Honkai Impact 3',
        'aliases' => [
            'hi3',
            'hi3rd',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'gopaygames' => [
                'code' => 'HONKAI_IMPACT',
            ],
        ],
    ],
    'laplacem' => [
        'code' => 'laplacem',
        'label' => 'Laplace M',
        'aliases' => [
            'zlongame',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 25528,
                'price' => '739000.0',
                'voucherTypeName' => 'ZLONGAME',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'wildrift' => [
        'code' => 'wildrift',
        'label' => 'League of Legends: Wild Rift',
        'aliases' => [
            'lolwildrift',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 372111,
                'price' => '360000.0',
                'voucherTypeName' => 'WILD_RIFT',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'lifeafter' => [
        'code' => 'lifeafter',
        'label' => 'LifeAfter',
        'aliases' => [
            'neteaselifeafter',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 45798,
                'price' => '1098977.0',
                'voucherTypeName' => 'NETEASE_LIFEAFTER',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
    ],
    'loveanddeepspace' => [
        'code' => 'loveanddeepspace',
        'label' => 'Love and Deepspace',
        'aliases' => [
            'lad',
        ],
        'requiresZone' => false,
        'status' => 'metadata-refresh-required',
        'providers' => [
            'codashop' => [
                'enabled' => false,
            ],
        ],
        'notes' => 'Requires short-lived SKU metadata; maintain it through a runtime token resolver.',
    ],
    'marvelduel' => [
        'code' => 'marvelduel',
        'label' => 'MARVEL Duel',
        'aliases' => [],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 155959,
                'price' => '739000.0',
                'voucherTypeName' => 'MARVEL_DUEL',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'metalslugawakening' => [
        'code' => 'metalslugawakening',
        'label' => 'Metal Slug: Awakening',
        'aliases' => [
            'metalslug',
            'msa',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'gopaygames' => [
                'code' => 'VNG_METAL_SLUG',
            ],
        ],
    ],
    'mobilelegends' => [
        'code' => 'mobilelegends',
        'label' => 'Mobile Legends: Bang Bang',
        'aliases' => [
            'ml',
            'mobilelegend',
            'mlbb',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'gopaygames' => [
                'code' => 'MOBILE_LEGENDS',
            ],
            'codashop' => [
                'vppId' => 5199,
                'price' => '68543.0000',
                'voucherTypeName' => 'MOBILE_LEGENDS',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
    ],
    'mobilelegendsadventure' => [
        'code' => 'mobilelegendsadventure',
        'label' => 'Mobile Legends: Adventure',
        'aliases' => [
            'mla',
            'adventure',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 36359,
                'price' => '739000.0',
                'voucherTypeName' => 'ADVENTURE',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
                'voucherTypeId' => 47,
            ],
        ],
    ],
    'muorigin2' => [
        'code' => 'muorigin2',
        'label' => 'MU Origin 2',
        'aliases' => [
            'ourpalm',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 16273,
                'price' => '550000.0',
                'voucherTypeName' => 'OURPALM',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
                'zoneFilter' => 'digits',
            ],
        ],
    ],
    'onepunchman' => [
        'code' => 'onepunchman',
        'label' => 'ONE PUNCH MAN: The Strongest',
        'aliases' => [
            'opm',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 77832,
                'price' => '5500000.0',
                'voucherTypeName' => 'ONE_PUNCH_MAN',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
    ],
    'onmyojiarena' => [
        'code' => 'onmyojiarena',
        'label' => 'Onmyoji Arena',
        'aliases' => [],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 46466,
                'price' => '706000.0',
                'voucherTypeName' => 'ONMYOJI_ARENA',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'pixelgun3d' => [
        'code' => 'pixelgun3d',
        'label' => 'Pixel Gun 3D',
        'aliases' => [
            'pg3d',
        ],
        'requiresZone' => false,
        'status' => 'metadata-refresh-required',
        'providers' => [
            'codashop' => [
                'enabled' => false,
            ],
        ],
        'notes' => 'Requires short-lived SKU metadata; no embedded token is distributed.',
    ],
    'pointblank' => [
        'code' => 'pointblank',
        'label' => 'Point Blank',
        'aliases' => [
            'pb',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 344845,
                'price' => '11000.0000',
                'voucherTypeName' => 'POINT_BLANK',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => '0',
            ],
        ],
    ],
    'ragnarokm' => [
        'code' => 'ragnarokm',
        'label' => 'Ragnarok M: Eternal Love',
        'aliases' => [
            'rom',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 414041,
                'price' => '1519050.0',
                'voucherTypeName' => 'GRAVITY_RAGNAROK_M',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
    ],
    'ragnarokx' => [
        'code' => 'ragnarokx',
        'label' => 'Ragnarok X: Next Generation',
        'aliases' => [
            'rox',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 195837,
                'price' => '1000000.0',
                'voucherTypeName' => 'RAGNAROK_X',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
    ],
    'sausageman' => [
        'code' => 'sausageman',
        'label' => 'Sausage Man',
        'aliases' => [],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 256634,
                'price' => '1599000.0',
                'voucherTypeName' => 'SAUSAGE_MAN',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'global-release',
            ],
        ],
    ],
    'speeddrifters' => [
        'code' => 'speeddrifters',
        'label' => 'Speed Drifters',
        'aliases' => [],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 12861,
                'price' => '1000000.0',
                'voucherTypeName' => 'SPEEDDRIFTERS',
                'nicknamePaths' => [
                    'confirmationFields.roles.0.role',
                    'confirmationFields.username',
                ],
            ],
        ],
    ],
    'supermechachampions' => [
        'code' => 'supermechachampions',
        'label' => 'Super Mecha Champions',
        'aliases' => [
            'smc',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 37815,
                'price' => '706000.0',
                'voucherTypeName' => 'SUPER_MECHA_CHAMPIONS',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'supersus' => [
        'code' => 'supersus',
        'label' => 'Super SUS',
        'aliases' => [],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 266162,
                'price' => '681000.0',
                'voucherTypeName' => 'SUPER_SUS',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'valorant' => [
        'code' => 'valorant',
        'label' => 'VALORANT',
        'aliases' => [
            'val',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'gopaygames' => [
                'code' => 'VALORANT',
            ],
            'codashop_dynamic' => [
                'pageUrl' => 'https://www.codashop.com/id-id/valorant',
                'productPath' => '/id/valorant',
                'skuId' => 'defa5e21-a4f3-40eb-835b-79c67d9b9263',
                'paymentChannelId' => 220,
                'voucherTypeName' => 'VALORANT',
                'voucherTypeId' => 109,
                'lvtId' => 1321,
                'gvtId' => 139,
                'vppId' => 3,
                'price' => '559000.0',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'warplanetonline' => [
        'code' => 'warplanetonline',
        'label' => 'War Planet Online',
        'aliases' => [
            'wpo',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 424705,
                'price' => '535000.0',
                'voucherTypeName' => 'WAR_PLANET_ONLINE',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'watcherofrealms' => [
        'code' => 'watcherofrealms',
        'label' => 'Watcher of Realms',
        'aliases' => [
            'wor',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 963012,
                'price' => '819000.0',
                'voucherTypeName' => 'WATCHER_OF_REALMS',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
    ],
    'zepeto' => [
        'code' => 'zepeto',
        'label' => 'ZEPETO',
        'aliases' => [
            'naverzcorporation',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 937273,
                'price' => '1082050.0',
                'voucherTypeName' => 'NAVER_Z_CORPORATION',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
            ],
        ],
    ],
    'fcmobile' => [
        'code' => 'fcmobile',
        'label' => 'FC Mobile',
        'aliases' => [
            'fcm',
            'eafcmobile',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'codashop_dynamic' => [
                'pageSlug' => 'ea-sports-fc-mobile',
                'storefronts' => [
                    [
                        'name' => 'bangladesh-en',
                        'localePath' => 'en-bd',
                    ],
                    [
                        'name' => 'malaysia-en',
                        'localePath' => 'en-my',
                    ],
                    [
                        'name' => 'hong-kong-en',
                        'localePath' => 'en-hk',
                    ],
                ],
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                    'confirmationFields.apiResult',
                    'confirmationFields.nickname',
                ],
            ],
            'gopaygames' => [
                'code' => 'FC_MOBILE',
            ],
        ],
        'notes' => 'Codashop regional discovery is preferred; GoPay Games remains a fallback.',
    ],
    'honorofkings' => [
        'code' => 'honorofkings',
        'label' => 'Honor of Kings',
        'aliases' => [
            'hok',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'gopaygames' => [
                'code' => 'HOK',
            ],
        ],
    ],
    'magicchessgogo' => [
        'code' => 'magicchessgogo',
        'label' => 'Magic Chess: Go Go',
        'aliases' => [
            'magicchess',
            'mcgg',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'gopaygames' => [
                'code' => 'MAGIC_CHESS_GO_GO',
            ],
        ],
    ],
    'pubgmobile' => [
        'code' => 'pubgmobile',
        'label' => 'PUBG Mobile',
        'aliases' => [
            'pubg',
            'pubgm',
            'pubgid',
        ],
        'requiresZone' => false,
        'status' => 'active',
        'providers' => [
            'midasbuy' => [
                'enabled' => true,
                'endpoint' => 'https://www.midasbuy.com/interface/getCharac',
                'referer' => 'https://www.midasbuy.com/common-sdk?id=playerid_enter&appid=1450015065&country=bd&removeIframeBeforeLoad=true&from=self.midasbuy_saas&lang=en&shopcode=midasbuy',
                'appId' => '1450015065',
                'zoneId' => '1',
            ],
            'gopaygames' => [
                'code' => 'PUBG_ID',
            ],
            'midasbuy_browser' => [
                'enabled' => true,
                'pageUrl' => 'https://www.midasbuy.com/midasbuy/bd/buy/pubgm',
                'timeout' => 90,
            ],
        ],
        'notes' => 'PHP generates the official Midasbuy getCharac encrypted payload for any Player ID and runs it first as direct HTTP, followed by GoPay Games. Browser validation remains the final optional fallback; an expired encryption session is non-fatal.',
    ],
    'zenlesszonezero' => [
        'code' => 'zenlesszonezero',
        'label' => 'Zenless Zone Zero',
        'aliases' => [
            'zzz',
        ],
        'requiresZone' => true,
        'status' => 'active',
        'providers' => [
            'codashop' => [
                'vppId' => 1044968,
                'price' => '16500.0000',
                'voucherTypeName' => 'ZENLESS_ZONE_ZERO',
                'nicknamePaths' => [
                    'confirmationFields.username',
                    'confirmationFields.roles.0.role',
                ],
                'zone' => 'user',
            ],
        ],
        'servers' => [
            'osusa' => 'prod_gf_us',
            'oseuro' => 'prod_gf_eu',
            'osasia' => 'prod_gf_jp',
            'oscht' => 'prod_gf_sg',
        ],
    ],
];
