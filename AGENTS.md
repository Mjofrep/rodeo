# Repository Guidance

- This is a dependency-free PHP 8.5 application intended for MAMP. Start MySQL on `localhost:8889`; PDO uses `127.0.0.1:8889` so macOS does not select a socket and ignore MAMP's port. The app initializes database `rodeo` and table `rodeo` on first request.
- Run locally with `php -S localhost:8000` from the repository root when MAMP is unavailable.
- `app.php` is the single source of truth for MySQL settings, the four placeholder teams, judges, and both rubric definitions. Every judge evaluates both competitions; keep rubric point values and critical flags aligned with `Rubricas_dos_competencias_Torneo_MT.docx`.
- `evaluar.php` is the stable evaluation route. `api.php` persists one evaluation per judge, team, and competition, so reopening the same judge/activity URL restores its saved state. The public board uses the average of four judge scores for each competition.
- A critical criterion rated `NC` must persist the evaluation as `suspended`; do not implement a path that treats it as a completed evaluation without an explicit safety-review workflow.
