# GitHub repository setup

Recommended repository name:

```text
game-account-lookup
```

## First upload

Create an empty **private** repository under `refatbd` without generating a
README, license, or `.gitignore`, because these files are already included.
This build contains rotating Garena and Midasbuy session values and must not be
made public without sanitizing them first.

Then run inside the extracted folder:

```bash
git init
git branch -M main
git add .
git commit -m "Release v0.7.0"
git remote add origin https://github.com/refatbd/game-account-lookup.git
git push -u origin main
```

## Recommended GitHub settings

- Enable Issues and Discussions.
- Enable private vulnerability reporting.
- Protect `main`.
- Require the `test` GitHub Actions job before merging.
- Require pull requests and at least one approval when collaborators join.
- Enable Dependabot security updates.
- Add topics: `php`, `laravel`, `game-api`, `uid`, `nickname`, `free-fire`,
  `pubg-mobile`, `mobile-legends`.
- Add the repository description from `composer.json`.

## First release

After GitHub Actions passes:

```bash
git tag -a v0.7.0 -m "Game Account Lookup v0.7.0"
git push origin v0.7.0
```

Do not submit this credential-bearing private build to public Packagist. Install
it from the private Git repository or use it directly after running
`composer install`.
