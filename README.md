# Kompasio Deploy

Handles shipping of the Kompasio web to shared hosting via
[Deployer](https://deployer.org). Two separate repositories are pulled and
combined into a single release on the server:

```
┌─────────────────┐    ┌──────────────┐
│  czetech/       │    │  cassiopea/  │
│  kompasio-site  │    │  kompasio    │
│  (Astro)        │    │  (PHP)       │
└────────┬────────┘    └────────┬─────┘
         │                      │
         │ npm                  │ composer
         │                      │
         └───────────┐   ┌──────┘
                     ▼   ▼
                ┌──────────────┐
                │  Websupport  │
                └──────────────┘
```

## Deploy pipeline

1. `deploy:prepare` — set up release directory structure
2. `deploy:vendors` — install PHP dependencies via Composer
3. `deploy:astro` — clone, build, and copy Astro output into `www/`
4. `deploy:htaccess` — upload `.htaccess` from this repo
5. `deploy:publish` — symlink the new release as `current`

## Usage

```
dep deploy
```
