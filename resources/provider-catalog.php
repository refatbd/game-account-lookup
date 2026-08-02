<?php

declare(strict_types=1);

/**
 * Provider availability snapshot.
 *
 * This file is intentionally separate from resources/games.php so provider
 * storefront changes can be audited and refreshed without rewriting legacy
 * game metadata. Initial full audit: 2026-07-31; latest entries: 2026-08-01.
 */
return [
    '8ballpool' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                '8-ball-pool',
            ],
            'storefronts' => [
                'en-bd',
                'en-sg',
                'en-us',
                'en-my',
                'en-kh',
            ],
            'productUrl' => 'https://www.codashop.com/en-bd/8-ball-pool',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'aethergazer' => [
        'codashop' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-global-sitemap-and-live-pages',
            'note' => 'No current Codashop product route was found across the global sitemap; former regional pages return 404.',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'aov' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                'arena-of-valor',
                'aov',
            ],
            'storefronts' => [
                'id-id',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/arena-of-valor',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/arena-of-valor',
            'code' => 'AOV',
        ],
    ],
    'asphalt9' => [
        'codashop' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-global-sitemap-and-live-pages',
            'note' => 'No current Codashop product route was found across the global sitemap; former regional pages return 404.',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'au2mobile' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-direct-id-form',
            'pageSlugs' => [
                'au2-mobile',
            ],
            'storefronts' => [
                'en-kh',
                'km-kh',
            ],
            'productUrl' => 'https://www.codashop.com/en-kh/au2-mobile',
            'note' => 'Cambodia storefronts expose a direct Au2 Mobile ID form.',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'autochess' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                'auto-chess',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
                'en-us',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/auto-chess',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'azurlane' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                'azur-lane',
            ],
            'storefronts' => [
                'id-id',
                'en-ph',
                'en-sg',
                'en-my',
                'pt-br',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/azur-lane',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'badlanders' => [
        'codashop' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'note' => 'Only historical Codashop editorial pages were found; no current direct product page was verified.',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
        ],
    ],
    'barbarq' => [
        'codashop' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-global-sitemap-and-live-pages',
            'note' => 'No current Codashop product route was found across the global sitemap; former regional pages return 404.',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'basketrio' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'basketrio',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/basketrio',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'cod' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'call-of-duty-mobile',
            ],
            'storefronts' => [
                'id-id',
                'pt-br',
                'tr-tr',
                'en-lk',
                'en-ng',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/call-of-duty-mobile',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-account-validation',
            'productPage' => 'https://gopay.co.id/games/call-of-duty-mobile-id',
            'code' => 'CALL_OF_DUTY',
        ],
    ],
    'captaintsubasa' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'captain-tsubasa-dream-team',
                'captain-tsubasa',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/captain-tsubasa-dream-team',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'crisisaction' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'crisis-action',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/crisis-action',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'dragoncity' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'dragon-city',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
                'pt-br',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/dragon-city',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'dragonraja' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'dragon-raja',
            ],
            'storefronts' => [
                'id-id',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/dragon-raja',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'eosred' => [
        'codashop' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-global-sitemap-and-live-pages',
            'note' => 'No current Codashop product route was found across the global sitemap; former regional pages return 404.',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'farlight84' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                'farlight-84',
            ],
            'storefronts' => [
                'en-my',
                'en-hk',
                'en-au',
                'en-ca',
                'pt-br',
            ],
            'productUrl' => 'https://www.codashop.com/en-my/farlight-84',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'footballmaster2' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'football-master-2',
                'football-master-2-soccer-star',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/football-master-2',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/football-master-2',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'freefire' => [
        'garena' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'live-account-endpoint',
            'productUrl' => 'https://shop2game.com/app/100067/idlogin',
            'note' => 'Official Shop2Game player-ID login returns nickname and region; DataDome verification may require server-side cookie configuration.',
        ],
        'codashop_browser' => [
            'status' => 'available',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'live-browser-confirmation',
            'productUrl' => 'https://www.codashop.com/en-my/free-fire',
            'note' => 'Requires local Node.js and a persistent user-visible Chrome session; never confirms payment.',
        ],
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                'free-fire',
                'garena-free-fire-ph',
            ],
            'storefronts' => [
                'en-my',
                'ms-my',
                'en-ph',
                'id-id',
            ],
            'productUrl' => 'https://www.codashop.com/en-my/free-fire',
            'note' => 'Nuxt 3 endpoints require pageLockToken (CAPTCHA). Falls back to legacy Indonesia endpoint, working only for Indonesian UIDs.',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/free-fire',
            'code' => 'FREEFIRE',
            'note' => 'GoPay is an Indonesia storefront and may reject accounts from other regions.',
        ],
    ],
    'freefiremax' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-direct-id-form',
            'pageSlugs' => [
                'free-fire-max',
            ],
            'storefronts' => [
                'id-id',
                'my-mm',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/free-fire-max',
            'note' => 'Indonesia and Myanmar expose direct Player ID pages. Other regional Free Fire MAX listings are Garena Shell vouchers and are intentionally excluded.',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-account-validation',
            'productPage' => 'https://gopay.co.id/games/free-fire-max',
            'code' => 'FREEFIRE',
            'note' => 'Uses GoPay Games direct account validation; Indonesian regional restrictions may apply.',
        ],
    ],
    'genshinimpact' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'genshin-impact',
            ],
            'storefronts' => [
                [
                    'storefront' => 'en-us',
                    'vppId' => 1,
                    'price' => '0.99',
                    'skuId' => 'ys_glb_primogem1ststall_tier1',
                    'paymentChannelId' => 1006,
                    'pcId' => 1006,
                    'gvtId' => 183,
                    'lvtId' => 3965,
                    'voucherTypeId' => 149,
                    'voucherTypeName' => 'GENSHIN_IMPACT',
                    'shopLang' => 'en_US',
                    'initEndpoint' => 'https://order-us.codashop.com/initPayment.action',
                    'productPath' => '/us/genshin-impact',
                ],
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/genshin-impact',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/genshin-impact',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'growtopia' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                'growtopia',
            ],
            'storefronts' => [
                'id-id',
                'en-bd',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/growtopia',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/growtopia',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'hago' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'hago',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/hago',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/hago',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'honkaistarrail' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'honkai-star-rail',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/honkai-star-rail',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/honkai-star-rail',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'honkaiimpact3' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-direct-id-form',
            'pageSlugs' => [
                'honkai-impact-3',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
                'th-th',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/honkai-impact-3',
            'note' => 'Verified storefronts expose a direct Honkai Impact 3 User ID form.',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-account-validation',
            'productPage' => 'https://gopay.co.id/games/honkai-impact-3',
            'code' => 'HONKAI_IMPACT',
        ],
    ],
    'laplacem' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'laplace-m',
            ],
            'storefronts' => [
                'id-id',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/laplace-m',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'wildrift' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                'league-of-legends-wild-rift',
                'wild-rift',
            ],
            'storefronts' => [
                'id-id',
                'en-ph',
                'en-my',
                'en-eg',
                'my-mm',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/league-of-legends-wild-rift',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/league-of-legends-wild-rift',
            'codeCandidates' => [
                'WILD_RIFT',
            ],
        ],
    ],
    'lifeafter' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'lifeafter',
                'life-after',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'my-mm',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/lifeafter',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-account-validation',
            'productPage' => 'https://gopay.co.id/games/lifeafter',
            'codeCandidates' => [
                'NETEASE_LIFEAFTER',
                'LIFEAFTER',
                'LIFE_AFTER',
            ],
        ],
    ],
    'loveanddeepspace' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                'love-and-deepspace',
            ],
            'storefronts' => [
                'id-id',
                'en-sg',
                'en-kh',
                'th-th',
                'en-ph',
            ],
            'productUrl' => 'https://www.codashop.com/en-sg/love-and-deepspace',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/love-and-deepspace',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'marvelduel' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'marvel-duel',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/marvel-duel',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/marvel-duel',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'metalslugawakening' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-direct-id-form',
            'pageSlugs' => [
                'metal-slug-awakening',
            ],
            'storefronts' => [
                'en-my',
                'id-id',
                'en-ph',
                'en-sg',
                'th-th',
            ],
            'productUrl' => 'https://www.codashop.com/en-my/metal-slug-awakening',
            'note' => 'Verified storefronts expose a direct Role ID form.',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-account-validation',
            'productPage' => 'https://gopay.co.id/games/metal-slug-awakening',
            'code' => 'VNG_METAL_SLUG',
        ],
    ],
    'mobilelegends' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'mobile-legends',
                'mobile-legends-bang-bang',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
                'en-bd',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/mobile-legends',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-account-validation',
            'productPage' => 'https://gopay.co.id/games/mobile-legends-bang-bang',
            'code' => 'MOBILE_LEGENDS',
        ],
    ],
    'mobilelegendsadventure' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'mobile-legends-adventure',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/mobile-legends-adventure',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'muorigin2' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'mu-origin-2',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/mu-origin-2',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/mu-origin-2',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'onepunchman' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'one-punch-man-the-strongest',
                'one-punch-man',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/one-punch-man-the-strongest',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/one-punch-man-the-strongest',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'onmyojiarena' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'onmyoji-arena',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/onmyoji-arena',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/onmyoji-arena',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'pixelgun3d' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'pixel-gun-3d',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
                'vi-vn',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/pixel-gun-3d',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/pixel-gun-3d',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'pointblank' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'point-blank',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/point-blank',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/point-blank',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'ragnarokm' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-global-sitemap-and-live-direct-id-form',
            'pageSlugs' => [
                'ragnarok-m-eternal-love-big-cat-coin',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
                'th-th',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/ragnarok-m-eternal-love-big-cat-coin',
            'note' => 'Codashop replaced the former Ragnarok M slug with the Big Cat Coin product route.',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'ragnarokx' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'ragnarok-x-next-generation',
                'ragnarok-x',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/ragnarok-x-next-generation',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/ragnarok-x-next-generation',
            'codeCandidates' => [
                'RAGNAROK_X',
                'RAGNAROK_X_NEXT_GENERATION',
            ],
        ],
    ],
    'sausageman' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'sausage-man',
            ],
            'storefronts' => [
                'id-id',
                'en-ph',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/sausage-man',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'speeddrifters' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'speed-drifters',
            ],
            'storefronts' => [
                'id-id',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/speed-drifters',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/speed-drifters',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'supermechachampions' => [
        'codashop' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'note' => 'Only historical Codashop editorial pages were found; no current direct product page was verified.',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
        ],
    ],
    'supersus' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'super-sus',
                'supersus',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
                'en-gb',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/super-sus',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'note' => 'No current GoPay Games listing was verified.',
        ],
    ],
    'valorant' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                'valorant',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-kh',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/valorant',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/valorant',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'warplanetonline' => [
        'codashop' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'note' => 'Only historical Codashop editorial pages were found; no current direct product page was verified.',
        ],
        'gopaygames' => [
            'status' => 'not-listed',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
        ],
    ],
    'watcherofrealms' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'watcher-of-realms',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
                'en-us',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/watcher-of-realms',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/watcher-of-realms',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'zepeto' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'zepeto',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
                'en-gb',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/zepeto',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/zepeto',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
    'fcmobile' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'pageSlugs' => [
                'ea-sports-fc-mobile',
                'fc-mobile',
            ],
            'storefronts' => [
                'en-bd',
                'en-my',
                'en-hk',
                'id-id',
            ],
            'productUrl' => 'https://www.codashop.com/en-bd/ea-sports-fc-mobile',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/ea-sports-fc-mobile',
            'code' => 'FC_MOBILE',
        ],
    ],
    'honorofkings' => [
        'codashop' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-voucher-form',
            'productUrl' => 'https://www.codashop.com/en-us/honor-of-kings',
            'note' => 'Codashop sells emailed voucher codes and does not expose a Player ID confirmation form.',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/honor-of-kings',
            'code' => 'HOK',
        ],
    ],
    'magicchessgogo' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-direct-id-form',
            'pageSlugs' => [
                'magic-chess-go-go',
            ],
            'storefronts' => [
                'en-us',
                'id-id',
                'en-my',
                'en-ph',
                'en-sg',
            ],
            'productUrl' => 'https://www.codashop.com/en-us/magic-chess-go-go',
            'note' => 'Verified storefronts expose User ID and Zone ID fields.',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/magic-chess-go-go',
            'code' => 'MAGIC_CHESS_GO_GO',
        ],
    ],
    'pubgmobile' => [
        'midasbuy' => [
            'status' => 'available',
            'verifiedAt' => '2026-07-31',
            'evidence' => 'official-live-player-validation',
            'productPage' => 'https://www.midasbuy.com/midasbuy/bd/buy/pubgm',
            'note' => 'Live PUBG Mobile Player ID validation uses an xMidas-encrypted per-page ctoken request. The browser adapter is an optional final fallback and returns nickname, game openid, zone, ban state, and country when Midasbuy supplies it.',
        ],
        'codashop' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productUrl' => 'https://www.codashop.com/en-bd/pubg-mobile',
            'note' => 'Current Codashop presence is voucher/external checkout rather than a bundled direct nickname-validation route.',
        ],
        'gopaygames' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-live-account-validation',
            'productPage' => 'https://gopay.co.id/games/pubg-mobile-global',
            'code' => 'PUBGM',
        ],
    ],
    'zenlesszonezero' => [
        'codashop' => [
            'status' => 'available',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-current-catalog',
            'pageSlugs' => [
                'zenless-zone-zero',
            ],
            'storefronts' => [
                'id-id',
                'en-my',
                'en-ph',
                'en-us',
                'pt-br',
            ],
            'productUrl' => 'https://www.codashop.com/id-id/zenless-zone-zero',
        ],
        'gopaygames' => [
            'status' => 'voucher-or-external',
            'verifiedAt' => '2026-08-01',
            'evidence' => 'official-product-page',
            'productPage' => 'https://gopay.co.id/games/zenless-zone-zero',
            'note' => 'The current storefront product uses NOVERIFY and does not expose nickname validation.',
        ],
    ],
];
