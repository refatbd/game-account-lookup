# Provider Availability Audit

**Latest verification date:** 2026-08-01  
**Maintainer:** [RefatBD](https://github.com/refatbd)

This document records the current public-storefront audit used by the default registry. The machine-readable source is [`resources/provider-catalog.php`](../resources/provider-catalog.php).

> Provider audit snapshot through (**2026-08-01**): **53 game definitions**, **46 active**, **44 Codashop direct-ID listings**, **14 GoPay direct-ID listings**, and **0 GoPay listings under maintenance**.

## What changed in v0.7.0

- Every currently verified Codashop direct-ID title now uses runtime product-page metadata discovery and regional storefront retry.
- Legacy fixed Codashop price-point metadata is retained only as a final compatibility fallback.
- The Codashop audit now checks the global country index and sitemap so a product missing in one country is not treated as globally unavailable.
- Added Codashop direct-ID routes for AU2 Mobile, Free Fire MAX, Honkai Impact 3, Magic Chess: Go Go and Metal Slug: Awakening.
- Updated Ragnarok M to the current `ragnarok-m-eternal-love-big-cat-coin` route and removed obsolete regional fallbacks.
- Aether Gazer, Asphalt 9, BarbarQ and EOS RED are no longer marked Codashop-available because no current global product route was found.
- Honor of Kings remains present on Codashop, but is classified as voucher/external because its regional pages deliver redeem codes instead of validating a Player ID.
- Current GoPay product pages are preflighted before account validation; product codes can be discovered from Next.js/RSC metadata.
- GoPay products that publicly report maintenance are disabled instead of producing misleading player errors.
- Badlanders, Super Mecha Champions and War Planet Online are no longer marked active because no current direct product listing was verified.
- PUBG Mobile's Codashop presence is classified as voucher/external rather than a bundled direct nickname-validation route.

## Latest GoPay audit additions

- Added direct account-validation routes for Free Fire MAX, Honkai Impact 3 and Metal Slug: Awakening.
- Updated Call of Duty Mobile, Mobile Legends and PUBG Mobile to their current official product slugs; PUBG Mobile now uses the current `PUBGM` code.
- Current RSC products whose code is `NOVERIFY` are classified as voucher/external and are not enabled as nickname providers.

## Audit methodology

1. Enumerate Codashop's global country index and current sitemap instead of relying on one regional homepage.
2. Confirm a current official product page in every retained fallback region.
3. Inspect whether the page accepts a Player/User/Role ID (and Zone ID where required) or only sells a voucher/redeem code.
4. Inspect the live GoPay RSC product code; an account form using `NOVERIFY` is not a nickname-validation route.
5. Classify direct-ID, maintenance, voucher/external, or not-listed status.
6. Enable only direct-ID routes in the default registry.
7. Keep all evidence dates and URLs in the machine-readable catalog.

This is a storefront-availability audit. It does not submit purchases and does not claim an official public nickname API.

## Complete provider matrix

| Game | Code | Provider | Audit state | Verified | Official evidence |
|---|---|---|---|---|---|
| 8 Ball Pool | `8ballpool` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/en-bd/8-ball-pool) |
| 8 Ball Pool | `8ballpool` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Aether Gazer | `aethergazer` | `codashop` | `not-listed` | 2026-08-01 | — |
| Aether Gazer | `aethergazer` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Arena of Valor | `aov` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/arena-of-valor) |
| Arena of Valor | `aov` | `gopaygames` | `available` | 2026-07-31 | [Official page](https://gopay.co.id/games/arena-of-valor) |
| Asphalt 9: Legends | `asphalt9` | `codashop` | `not-listed` | 2026-08-01 | — |
| Asphalt 9: Legends | `asphalt9` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| AU2 Mobile | `au2mobile` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/en-kh/au2-mobile) |
| AU2 Mobile | `au2mobile` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Auto Chess | `autochess` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/auto-chess) |
| Auto Chess | `autochess` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Azur Lane | `azurlane` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/azur-lane) |
| Azur Lane | `azurlane` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Badlanders | `badlanders` | `codashop` | `not-listed` | 2026-08-01 | — |
| Badlanders | `badlanders` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| BarbarQ | `barbarq` | `codashop` | `not-listed` | 2026-08-01 | — |
| BarbarQ | `barbarq` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Basketrio | `basketrio` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/basketrio) |
| Basketrio | `basketrio` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Call of Duty Mobile | `cod` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/call-of-duty-mobile) |
| Call of Duty Mobile | `cod` | `gopaygames` | `available` | 2026-08-01 | [Official page](https://gopay.co.id/games/call-of-duty-mobile-id) |
| Captain Tsubasa: Dream Team | `captaintsubasa` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/captain-tsubasa-dream-team) |
| Captain Tsubasa: Dream Team | `captaintsubasa` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Crisis Action | `crisisaction` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/crisis-action) |
| Crisis Action | `crisisaction` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Dragon City | `dragoncity` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/dragon-city) |
| Dragon City | `dragoncity` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Dragon Raja | `dragonraja` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/dragon-raja) |
| Dragon Raja | `dragonraja` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| EOS RED | `eosred` | `codashop` | `not-listed` | 2026-08-01 | — |
| EOS RED | `eosred` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Farlight 84 | `farlight84` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/en-my/farlight-84) |
| Farlight 84 | `farlight84` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| FC Mobile | `fcmobile` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/en-bd/ea-sports-fc-mobile) |
| FC Mobile | `fcmobile` | `gopaygames` | `available` | 2026-07-31 | [Official page](https://gopay.co.id/games/ea-sports-fc-mobile) |
| Football Master 2 | `footballmaster2` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/football-master-2) |
| Football Master 2 | `footballmaster2` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/football-master-2) |
| Free Fire | `freefire` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/en-my/free-fire) |
| Free Fire | `freefire` | `gopaygames` | `available` | 2026-07-31 | [Official page](https://gopay.co.id/games/free-fire) |
| Free Fire MAX | `freefiremax` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/free-fire-max) |
| Free Fire MAX | `freefiremax` | `gopaygames` | `available` | 2026-08-01 | [Official page](https://gopay.co.id/games/free-fire-max) |
| Genshin Impact | `genshinimpact` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/genshin-impact) |
| Genshin Impact | `genshinimpact` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/genshin-impact) |
| Growtopia | `growtopia` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/growtopia) |
| Growtopia | `growtopia` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/growtopia) |
| Hago | `hago` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/hago) |
| Hago | `hago` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/hago) |
| Honkai Impact 3 | `honkaiimpact3` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/honkai-impact-3) |
| Honkai Impact 3 | `honkaiimpact3` | `gopaygames` | `available` | 2026-08-01 | [Official page](https://gopay.co.id/games/honkai-impact-3) |
| Honkai: Star Rail | `honkaistarrail` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/honkai-star-rail) |
| Honkai: Star Rail | `honkaistarrail` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/honkai-star-rail) |
| Honor of Kings | `honorofkings` | `codashop` | `voucher-or-external` | 2026-08-01 | [Official page](https://www.codashop.com/en-us/honor-of-kings) |
| Honor of Kings | `honorofkings` | `gopaygames` | `available` | 2026-07-31 | [Official page](https://gopay.co.id/games/honor-of-kings) |
| Laplace M | `laplacem` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/laplace-m) |
| Laplace M | `laplacem` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| League of Legends: Wild Rift | `wildrift` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/league-of-legends-wild-rift) |
| League of Legends: Wild Rift | `wildrift` | `gopaygames` | `available` | 2026-07-31 | [Official page](https://gopay.co.id/games/league-of-legends-wild-rift) |
| LifeAfter | `lifeafter` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/lifeafter) |
| LifeAfter | `lifeafter` | `gopaygames` | `available` | 2026-08-01 | [Official page](https://gopay.co.id/games/lifeafter) |
| Love and Deepspace | `loveanddeepspace` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/en-sg/love-and-deepspace) |
| Love and Deepspace | `loveanddeepspace` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/love-and-deepspace) |
| Magic Chess: Go Go | `magicchessgogo` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/en-us/magic-chess-go-go) |
| Magic Chess: Go Go | `magicchessgogo` | `gopaygames` | `available` | 2026-07-31 | [Official page](https://gopay.co.id/games/magic-chess-go-go) |
| MARVEL Duel | `marvelduel` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/marvel-duel) |
| MARVEL Duel | `marvelduel` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/marvel-duel) |
| Metal Slug: Awakening | `metalslugawakening` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/en-my/metal-slug-awakening) |
| Metal Slug: Awakening | `metalslugawakening` | `gopaygames` | `available` | 2026-08-01 | [Official page](https://gopay.co.id/games/metal-slug-awakening) |
| Mobile Legends: Adventure | `mobilelegendsadventure` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/mobile-legends-adventure) |
| Mobile Legends: Adventure | `mobilelegendsadventure` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Mobile Legends: Bang Bang | `mobilelegends` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/mobile-legends) |
| Mobile Legends: Bang Bang | `mobilelegends` | `gopaygames` | `available` | 2026-08-01 | [Official page](https://gopay.co.id/games/mobile-legends-bang-bang) |
| MU Origin 2 | `muorigin2` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/mu-origin-2) |
| MU Origin 2 | `muorigin2` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/mu-origin-2) |
| ONE PUNCH MAN: The Strongest | `onepunchman` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/one-punch-man-the-strongest) |
| ONE PUNCH MAN: The Strongest | `onepunchman` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/one-punch-man-the-strongest) |
| Onmyoji Arena | `onmyojiarena` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/onmyoji-arena) |
| Onmyoji Arena | `onmyojiarena` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/onmyoji-arena) |
| Pixel Gun 3D | `pixelgun3d` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/pixel-gun-3d) |
| Pixel Gun 3D | `pixelgun3d` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/pixel-gun-3d) |
| Point Blank | `pointblank` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/point-blank) |
| Point Blank | `pointblank` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/point-blank) |
| PUBG Mobile | `pubgmobile` | `codashop` | `voucher-or-external` | 2026-08-01 | [Official page](https://www.codashop.com/en-bd/pubg-mobile) |
| PUBG Mobile | `pubgmobile` | `gopaygames` | `available` | 2026-08-01 | [Official page](https://gopay.co.id/games/pubg-mobile-global) |
| Ragnarok M: Eternal Love | `ragnarokm` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/ragnarok-m-eternal-love-big-cat-coin) |
| Ragnarok M: Eternal Love | `ragnarokm` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Ragnarok X: Next Generation | `ragnarokx` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/ragnarok-x-next-generation) |
| Ragnarok X: Next Generation | `ragnarokx` | `gopaygames` | `available` | 2026-07-31 | [Official page](https://gopay.co.id/games/ragnarok-x-next-generation) |
| Sausage Man | `sausageman` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/sausage-man) |
| Sausage Man | `sausageman` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Speed Drifters | `speeddrifters` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/speed-drifters) |
| Speed Drifters | `speeddrifters` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/speed-drifters) |
| Super Mecha Champions | `supermechachampions` | `codashop` | `not-listed` | 2026-08-01 | — |
| Super Mecha Champions | `supermechachampions` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Super SUS | `supersus` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/super-sus) |
| Super SUS | `supersus` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| VALORANT | `valorant` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/valorant) |
| VALORANT | `valorant` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/valorant) |
| War Planet Online | `warplanetonline` | `codashop` | `not-listed` | 2026-08-01 | — |
| War Planet Online | `warplanetonline` | `gopaygames` | `not-listed` | 2026-07-31 | — |
| Watcher of Realms | `watcherofrealms` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/watcher-of-realms) |
| Watcher of Realms | `watcherofrealms` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/watcher-of-realms) |
| Zenless Zone Zero | `zenlesszonezero` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/zenless-zone-zero) |
| Zenless Zone Zero | `zenlesszonezero` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/zenless-zone-zero) |
| ZEPETO | `zepeto` | `codashop` | `available` | 2026-08-01 | [Official page](https://www.codashop.com/id-id/zepeto) |
| ZEPETO | `zepeto` | `gopaygames` | `voucher-or-external` | 2026-08-01 | [Official page](https://gopay.co.id/games/zepeto) |

## Refreshing the snapshot

```bash
php tools/audit-provider-catalog.php
php tools/audit-provider-catalog.php --json
php tools/audit-provider-catalog.php --live
composer docs:games
```

`--live` checks public product pages only. It does not submit a player ID or purchase. Review changes manually before editing `resources/provider-catalog.php`.
