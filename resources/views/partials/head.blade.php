<meta charset="utf-8" />
<!-- Prevent mobile double-tap / pinch zoom that interferes with touch UI; keep accessible fallback in mind -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

<style>
	/* Improve touch interaction: prevent double-tap zoom and make buttons respond to single taps */
	button, a, [role="button"], .flux\:button {
		-ms-touch-action: manipulation;
		touch-action: manipulation;
		-webkit-user-select: none;
		-webkit-tap-highlight-color: rgba(0,0,0,0.04);
	}

	/* Reduce accidental zoom on elements that may be rapidly tapped */
	input, textarea {
		-webkit-touch-callout: none;
	}
</style>
