# Supported Games

This file is generated from [`resources/games.php`](../resources/games.php) plus the dated availability catalog in [`resources/provider-catalog.php`](../resources/provider-catalog.php). Do not edit the table by hand.

```bash
composer docs:games
```

> Provider audit snapshot through (**2026-08-01**): **53 game definitions**, **46 active**, **44 Codashop direct-ID listings**, **14 GoPay direct-ID listings**, and **0 GoPay listings under maintenance**.

## Status meanings

| Status | Meaning |
|---|---|
| `active` | At least one currently audited bundled provider route is enabled. |
| `provider-unavailable` | Previously supported metadata remains for reference, but no currently verified bundled provider route is enabled. |
| `external-provider-required` | A separate maintained/authorized adapter is required. |

## Provider availability meanings

| Provider state | Meaning |
|---|---|
| `available` | An official current product page/direct-ID route was verified during the dated audit. It is still not an uptime guarantee. |
| `maintenance` | The official product page exists but currently reports maintenance; the adapter is disabled. |
| `not-listed` | No current direct product page was verified; stale adapters are disabled. |
| `voucher-or-external` | A store presence exists, but it is not a bundled direct nickname-validation flow. |

## Registry table

| Game | Code | Zone Required | Known Server Values | Codashop | GoPay | Other | Status | Aliases |
|---|---|:---:|---|---|---|---|---|---|
| 8 Ball Pool | `8ballpool` | No | — | [Available](https://www.codashop.com/en-bd/8-ball-pool)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `eightballpool`, `8bp` |
| Aether Gazer | `aethergazer` | No | — | Not listed | Not listed | — | `provider-unavailable` | `aether` |
| Arena of Valor | `aov` | No | — | [Available](https://www.codashop.com/id-id/arena-of-valor)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/arena-of-valor)<br><sub>page preflight + account API</sub> | — | `active` | `arenaofvalor` |
| Asphalt 9: Legends | `asphalt9` | Yes | Free-form | Not listed | Not listed | — | `provider-unavailable` | `gamelefta9`, `a9` |
| AU2 Mobile | `au2mobile` | No | — | [Available](https://www.codashop.com/en-kh/au2-mobile)<br><sub>dynamic regional</sub> | Not listed | `dancingidol` (disabled) | `active` | `autwomobile` |
| Auto Chess | `autochess` | No | — | [Available](https://www.codashop.com/id-id/auto-chess)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | — |
| Azur Lane | `azurlane` | Yes | `1`, `2`, `3`, `4`, `5`, `6` | [Available](https://www.codashop.com/id-id/azur-lane)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `azur` |
| Badlanders | `badlanders` | Yes | `11001`, `21004` | Not listed | Not listed | — | `provider-unavailable` | — |
| BarbarQ | `barbarq` | No | — | Not listed | Not listed | — | `provider-unavailable` | — |
| Basketrio | `basketrio` | Yes | `2`, `3`, `4` | [Available](https://www.codashop.com/id-id/basketrio)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | — |
| Call of Duty Mobile | `cod` | No | — | [Available](https://www.codashop.com/id-id/call-of-duty-mobile)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/call-of-duty-mobile-id)<br><sub>page preflight + account API</sub> | — | `active` | `codm`, `callofduty`, `callofdutymobile` |
| Captain Tsubasa: Dream Team | `captaintsubasa` | No | — | [Available](https://www.codashop.com/id-id/captain-tsubasa-dream-team)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `tsubasa` |
| Crisis Action | `crisisaction` | Yes | Free-form | [Available](https://www.codashop.com/id-id/crisis-action)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `caherogames` |
| Dragon City | `dragoncity` | No | — | [Available](https://www.codashop.com/id-id/dragon-city)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | — |
| Dragon Raja | `dragonraja` | No | — | [Available](https://www.codashop.com/id-id/dragon-raja)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `zulongdragonraja` |
| EOS RED | `eosred` | Yes | Free-form | Not listed | Not listed | — | `provider-unavailable` | — |
| Farlight 84 | `farlight84` | No | — | [Available](https://www.codashop.com/en-my/farlight-84)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `farlight` |
| FC Mobile | `fcmobile` | No | — | [Available](https://www.codashop.com/en-bd/ea-sports-fc-mobile)<br><sub>dynamic regional</sub> | [Available](https://gopay.co.id/games/ea-sports-fc-mobile)<br><sub>page preflight + account API</sub> | — | `active` | `fcm`, `eafcmobile` |
| Football Master 2 | `footballmaster2` | No | — | [Available](https://www.codashop.com/id-id/football-master-2)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/football-master-2) | — | `active` | `footballmaster` |
| Free Fire | `freefire` | No | — | [Available](https://www.codashop.com/en-my/free-fire)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/free-fire)<br><sub>page preflight + account API</sub> | `garena`, `codashop_browser` | `active` | `ff`, `garena` |
| Free Fire MAX | `freefiremax` | No | — | [Available](https://www.codashop.com/id-id/free-fire-max)<br><sub>dynamic regional</sub> | [Available](https://gopay.co.id/games/free-fire-max)<br><sub>page preflight + account API</sub> | — | `active` | `ffmax` |
| Genshin Impact | `genshinimpact` | Yes | `os_usa`, `os_euro`, `os_asia`, `os_cht` | [Available](https://www.codashop.com/id-id/genshin-impact)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/genshin-impact) | — | `active` | `genshin`, `gi` |
| Growtopia | `growtopia` | No | — | [Available](https://www.codashop.com/id-id/growtopia)<br><sub>dynamic regional</sub> | [Voucher/external only](https://gopay.co.id/games/growtopia) | — | `active` | `gt` |
| Hago | `hago` | No | — | [Available](https://www.codashop.com/id-id/hago)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/hago) | — | `active` | — |
| Honkai Impact 3 | `honkaiimpact3` | No | — | [Available](https://www.codashop.com/id-id/honkai-impact-3)<br><sub>dynamic regional</sub> | [Available](https://gopay.co.id/games/honkai-impact-3)<br><sub>page preflight + account API</sub> | — | `active` | `hi3`, `hi3rd` |
| Honkai: Star Rail | `honkaistarrail` | Yes | `prod_official_usa`, `prod_official_eur`, `prod_official_asia`, `prod_official_cht` | [Available](https://www.codashop.com/id-id/honkai-star-rail)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/honkai-star-rail) | — | `active` | `hsr`, `starrail` |
| Honor of Kings | `honorofkings` | No | — | [Voucher/external only](https://www.codashop.com/en-us/honor-of-kings) | [Available](https://gopay.co.id/games/honor-of-kings)<br><sub>page preflight + account API</sub> | — | `active` | `hok` |
| Laplace M | `laplacem` | No | — | [Available](https://www.codashop.com/id-id/laplace-m)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `zlongame` |
| League of Legends: Wild Rift | `wildrift` | No | — | [Available](https://www.codashop.com/id-id/league-of-legends-wild-rift)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/league-of-legends-wild-rift)<br><sub>page preflight + account API</sub> | — | `active` | `lolwildrift` |
| LifeAfter | `lifeafter` | Yes | Free-form | [Available](https://www.codashop.com/id-id/lifeafter)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/lifeafter)<br><sub>page preflight + account API</sub> | — | `active` | `neteaselifeafter` |
| Love and Deepspace | `loveanddeepspace` | No | — | [Available](https://www.codashop.com/en-sg/love-and-deepspace)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/love-and-deepspace) | — | `active` | `lad` |
| Magic Chess: Go Go | `magicchessgogo` | Yes | Free-form | [Available](https://www.codashop.com/en-us/magic-chess-go-go)<br><sub>dynamic regional</sub> | [Available](https://gopay.co.id/games/magic-chess-go-go)<br><sub>page preflight + account API</sub> | — | `active` | `magicchess`, `mcgg` |
| MARVEL Duel | `marvelduel` | No | — | [Available](https://www.codashop.com/id-id/marvel-duel)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/marvel-duel) | — | `active` | — |
| Metal Slug: Awakening | `metalslugawakening` | No | — | [Available](https://www.codashop.com/en-my/metal-slug-awakening)<br><sub>dynamic regional</sub> | [Available](https://gopay.co.id/games/metal-slug-awakening)<br><sub>page preflight + account API</sub> | — | `active` | `metalslug`, `msa` |
| Mobile Legends: Adventure | `mobilelegendsadventure` | Yes | Free-form | [Available](https://www.codashop.com/id-id/mobile-legends-adventure)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `mla`, `adventure` |
| Mobile Legends: Bang Bang | `mobilelegends` | Yes | Free-form | [Available](https://www.codashop.com/id-id/mobile-legends)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/mobile-legends-bang-bang)<br><sub>page preflight + account API</sub> | — | `active` | `ml`, `mobilelegend`, `mlbb` |
| MU Origin 2 | `muorigin2` | Yes | Free-form | [Available](https://www.codashop.com/id-id/mu-origin-2)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/mu-origin-2) | — | `active` | `ourpalm` |
| ONE PUNCH MAN: The Strongest | `onepunchman` | Yes | Free-form | [Available](https://www.codashop.com/id-id/one-punch-man-the-strongest)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/one-punch-man-the-strongest) | — | `active` | `opm` |
| Onmyoji Arena | `onmyojiarena` | No | — | [Available](https://www.codashop.com/id-id/onmyoji-arena)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/onmyoji-arena) | — | `active` | — |
| Pixel Gun 3D | `pixelgun3d` | No | — | [Available](https://www.codashop.com/id-id/pixel-gun-3d)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/pixel-gun-3d) | — | `active` | `pg3d` |
| Point Blank | `pointblank` | No | — | [Available](https://www.codashop.com/id-id/point-blank)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/point-blank) | — | `active` | `pb` |
| PUBG Mobile | `pubgmobile` | No | — | [Voucher/external only](https://www.codashop.com/en-bd/pubg-mobile) | [Available](https://gopay.co.id/games/pubg-mobile-global)<br><sub>page preflight + account API</sub> | `midasbuy`, `midasbuy_browser` | `active` | `pubg`, `pubgm`, `pubgid` |
| Ragnarok M: Eternal Love | `ragnarokm` | Yes | Free-form | [Available](https://www.codashop.com/id-id/ragnarok-m-eternal-love-big-cat-coin)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `rom` |
| Ragnarok X: Next Generation | `ragnarokx` | Yes | Free-form | [Available](https://www.codashop.com/id-id/ragnarok-x-next-generation)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/ragnarok-x-next-generation)<br><sub>page preflight + account API</sub> | — | `active` | `rox` |
| Sausage Man | `sausageman` | No | — | [Available](https://www.codashop.com/id-id/sausage-man)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | — |
| Speed Drifters | `speeddrifters` | No | — | [Available](https://www.codashop.com/id-id/speed-drifters)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/speed-drifters) | — | `active` | — |
| Super Mecha Champions | `supermechachampions` | No | — | Not listed | Not listed | — | `provider-unavailable` | `smc` |
| Super SUS | `supersus` | No | — | [Available](https://www.codashop.com/id-id/super-sus)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | — |
| VALORANT | `valorant` | No | — | [Available](https://www.codashop.com/id-id/valorant)<br><sub>dynamic regional</sub> | [Voucher/external only](https://gopay.co.id/games/valorant) | — | `active` | `val` |
| War Planet Online | `warplanetonline` | No | — | Not listed | Not listed | — | `provider-unavailable` | `wpo` |
| Watcher of Realms | `watcherofrealms` | Yes | Free-form | [Available](https://www.codashop.com/id-id/watcher-of-realms)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/watcher-of-realms) | — | `active` | `wor` |
| Zenless Zone Zero | `zenlesszonezero` | Yes | `prod_gf_us`, `prod_gf_eu`, `prod_gf_jp`, `prod_gf_sg` | [Available](https://www.codashop.com/id-id/zenless-zone-zero)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/zenless-zone-zero) | — | `active` | `zzz` |
| ZEPETO | `zepeto` | No | — | [Available](https://www.codashop.com/id-id/zepeto)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/zepeto) | — | `active` | `naverzcorporation` |

## Availability policy

The catalog is a dated snapshot, not a permanent guarantee. Provider pages, SKU metadata, internal codes, regional restrictions and account-validation behavior can change without notice. Before publishing a release, run the provider audit tool and verify permitted valid/invalid test accounts. Never commit cookies, access tokens, personal account data or short-lived JWT values.
