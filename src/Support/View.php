<?php

declare(strict_types=1);

namespace AndroidForensicsLab\Support;

final class View
{
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function page(string $title, string $body): string
    {
        $safeTitle = self::e($title);

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeTitle}</title>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="/">Android Forensics Lab</a>
    <nav>
      <a href="/audit">Audit</a>
      <a href="/casework">Casework</a>
      <a href="/acquisition">Acquisition</a>
      <a href="/artifacts">Artifacts</a>
      <a href="/workbench">Workbench</a>
      <a href="/validation">Validation</a>
      <a href="/report-readiness">Report</a>
      <a href="/methods">Methods</a>
      <a href="/wiping">Wiping</a>
      <a href="/timeline">Timeline</a>
      <a href="/ledger">Ledger</a>
      <a href="/research">Research</a>
    </nav>
  </header>
  <main>{$body}</main>
  <footer>
    <span>Advanced Android digital forensics laboratory framework.</span>
    <span>Built for defensible research-aligned casework.</span>
  </footer>
</body>
</html>
HTML;
    }
}
