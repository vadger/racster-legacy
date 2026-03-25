<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>{{ $subjectLine ?? config('app.name') }}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		/* Basic reset */
		body, table, td, a {
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}
		img {
			border: 0;
			outline: none;
			text-decoration: none;
			-ms-interpolation-mode: bicubic;
			display: block;
		}
		table {
			border-collapse: collapse !important;
		}
		body {
			margin: 0;
			padding: 0;
			width: 100% !important;
			height: 100% !important;
		}
		.email-container p {
			margin-top: 0 !important;
		}
		.email-container .hero-title,
		.email-container ul,
		.email-container ol {
			margin-bottom: 0 !important;
		}
		.racster-inline-btn {
			display: inline-block;
			padding: 6px 12px;
			font-size: 14px;
			color: #fff !important;
			background-color: #00bf63;
			border: 1px solid #00bf63;
			border-radius: 6px;
			text-decoration: none !important;
			white-space:nowrap;
			font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
		}
		.racster-secondary {
			padding: 2px 4px;
			color: #2E2E2E !important;
			border-color: rgba(0,0,0,0.1);
			background-color: #f8f9fa;
		}
		.racster-link {
			padding: 2px 0px;
			color: #00bf63 !important;
			border: 0;
			background-color: transparent;
		}

		/* Responsive */
		@media screen and (max-width: 600px) {
			.email-container {
				width: 100% !important;
			}
			.p-24 {
				padding: 20px !important;
			}
			.hero-title {
				font-size: 24px !important;
				line-height: 32px !important;
			}
			.btn-primary span {
				display: block !important;
			}
		}
	</style>
</head>
<body style="background-color:#fff; margin:0; padding:24px 0;">

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td align="center" style="padding:0 16px;">

			<!-- Main container -->
			<div style="max-width:600px;
					background-color:rgba(0,0,0,0.02); border:1px solid rgba(81,188,27,0.3); border-top-width:3px; border-top-color: #00bf63; border-radius:6px;">
				<table border="0" cellpadding="0" cellspacing="0" width="600" class="email-container"
						style="max-width:600px; border-radius:6px; overflow:hidden;">

					<!-- Logo -->
					<tr>
						<td align="center" class="p-24" style="padding:24px 32px 8px 32px;">
							<a class="navbar-brand" href="{{ LaravelLocalization::localizeUrl('/') }}" target="_blank">
								<img src="{{ asset('/images/racster-logo.svg') }}" border="0"
									style="max-width:160px; height:auto;"
									alt="{{ config('app.name') }}" />
							</a>
						</td>
					</tr>

					<!-- Heading -->
					@if (!empty($htmlHeading))
						<tr>
							<td align="center" class="p-24" style="padding:12px 32px 12px 32px;">
								<h1 class="hero-title" style="margin:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
									font-size:26px; line-height:34px; font-weight:700; color:#2E2E2E;">
									{{ $htmlHeading }}
								</h1>
							</td>
						</tr>
					@endif

					<!-- Content block (single content you pass in) -->
					<tr>
						<td align="left" class="p-24" style="padding:12px 32px 12px 32px;">
							<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
											font-size:14px; line-height:22px; color:#2E2E2E;">
								{!! $htmlContent !!}
							</div>
						</td>
					</tr>

					<!-- Primary button -->
					@if (!empty($htmlCTAName) && !empty($htmlCTALink))
						<tr>
							<td align="center" class="p-24" style="padding:8px 32px 24px 32px;">
								<table border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td align="center" style="overflow:hidden;">
											<a href="{{ $htmlCTALink }}" class="btn-primary racster-inline-btn" target="_blank">
												<span>
													{{ $htmlCTAName }}
												</span>
											</a>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					@endif

					<!-- Footer -->
					<tr>
						<td align="center" style="padding:16px 32px 20px 32px; background-color:rgba(0,0,0,0.03);">
							<p style="margin:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
											font-size:11px; line-height:16px; color:#2E2E2E;">
								© {{ now()->year }} {{ config('app.name') }}. @lang('racster.subscription-notice-email-footer')
							</p>
						</td>
					</tr>

				</table>
			</div>
			<!-- /Main container -->

		</td>
	</tr>
</table>

</body>
</html>
