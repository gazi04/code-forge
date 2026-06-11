<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate &ndash; {{ $world->name }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #09090b;
            color: #f8fafc;
            font-family: 'Courier New', Courier, monospace;
            width: 297mm;
            height: 210mm;
        }

        .page {
            width: 297mm;
            height: 210mm;
            background: #09090b;
            position: relative;
            overflow: hidden;
        }

        /* Subtle dot-grid background */
        .grid-bg {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        /* Top accent bar */
        .top-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: {{ $primaryColor }};
        }

        /* Radial glow from top, like WorldMap hover */
        .top-glow {
            position: absolute;
            top: -60px;
            left: 50%;
            width: 400px;
            height: 200px;
            margin-left: -200px;
            background: radial-gradient(ellipse at center, {{ $primaryColor }} 0%, transparent 70%);
            opacity: 0.12;
        }

        /* Corner brackets */
        .corner {
            position: absolute;
            width: 28px;
            height: 28px;
        }
        .corner-tl { top: 16px; left: 16px; border-top: 2px solid {{ $primaryColor }}; border-left: 2px solid {{ $primaryColor }}; }
        .corner-tr { top: 16px; right: 16px; border-top: 2px solid {{ $primaryColor }}; border-right: 2px solid {{ $primaryColor }}; }
        .corner-bl { bottom: 16px; left: 16px; border-bottom: 2px solid {{ $primaryColor }}; border-left: 2px solid {{ $primaryColor }}; }
        .corner-br { bottom: 16px; right: 16px; border-bottom: 2px solid {{ $primaryColor }}; border-right: 2px solid {{ $primaryColor }}; }

        /* Outer border */
        .outer-border {
            position: absolute;
            inset: 10px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        /* Content */
        .content {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 44px 80px 0;
        }

        .brand {
            font-size: 14px;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.65);
            margin-bottom: 16px;
        }

        .glow-divider {
            width: 220px;
            height: 1px;
            margin: 0 auto 18px;
            background: linear-gradient(to right, transparent, {{ $primaryColor }}, transparent);
            opacity: 0.9;
        }

        .cert-label {
            font-size: 15px;
            letter-spacing: 5px;
            text-transform: uppercase;
            color: {{ $primaryColor }};
            margin-bottom: 8px;
        }

        .certifies-text {
            font-size: 14px;
            color: rgba(255,255,255,0.70);
            font-style: italic;
            margin-bottom: 14px;
        }

        .student-name {
            font-size: 48px;
            font-weight: bold;
            color: #ffffff;
            font-style: italic;
            font-family: Georgia, 'Times New Roman', serif;
            margin-bottom: 14px;
            text-shadow: 0 0 40px {{ $primaryColor }};
        }

        .completion-text {
            font-size: 13px;
            color: rgba(255,255,255,0.70);
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .world-name {
            font-size: 32px;
            font-weight: bold;
            color: {{ $primaryColor }};
            font-family: Georgia, 'Times New Roman', serif;
            margin: 8px 0 14px;
        }

        /* Footer */
        .footer-wrap {
            position: absolute;
            bottom: 28px;
            left: 80px;
            right: 80px;
        }

        .footer-divider {
            width: 100%;
            height: 1px;
            background: rgba(255,255,255,0.07);
            margin-bottom: 14px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            text-align: center;
            vertical-align: top;
            width: 33%;
        }

        .footer-value {
            font-size: 13px;
            color: rgba(255,255,255,0.90);
            letter-spacing: 0.5px;
        }

        .footer-label {
            font-size: 9px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: {{ $primaryColor }};
            margin-top: 4px;
            opacity: 1;
        }

        .footer-sep {
            display: inline-block;
            width: 32px;
            height: 32px;
            border: 2px solid {{ $primaryColor }};
            transform: rotate(45deg);
            opacity: 0.8;
        }

        .courses-wrap {
            margin-top: 16px;
            padding: 14px 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .courses-label {
            font-size: 12px;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.55);
            margin-bottom: 10px;
        }

        .course-tag {
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            color: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            padding: 4px 14px;
            margin: 3px 4px;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="grid-bg"></div>
        <div class="top-bar"></div>
        <div class="top-glow"></div>
        <div class="outer-border"></div>

        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>

        <div class="content">
            <div class="brand">CodeForge Academy</div>

            <div class="glow-divider"></div>

            <div class="cert-label">Certificate of Completion</div>

            <div class="certifies-text">This is to certify that</div>

            <div class="student-name">{{ $user->name }}</div>

            <div class="completion-text">has successfully completed all courses and lessons in the world of</div>

            <div class="world-name">{{ $world->name }}</div>

            <div class="completion-text">demonstrating dedication, problem-solving ability, and coding excellence.</div>

            @if($courses->isNotEmpty())
            <div class="courses-wrap">
                <div class="courses-label">Courses Completed</div>
                @foreach($courses as $course)
                    <span class="course-tag">{{ $course->name }}</span>
                @endforeach
            </div>
            @endif
        </div>

        <div class="footer-wrap">
            <div class="footer-divider"></div>
            <table class="footer-table">
                <tr>
                    <td>
                        <div class="footer-value">{{ $completedAt->format('F j, Y') }}</div>
                        <div class="footer-label">Date of Completion</div>
                    </td>
                    <td>
                        <div><span class="footer-sep"></span></div>
                    </td>
                    <td>
                        <div class="footer-value">CodeForge Academy</div>
                        <div class="footer-label">Issued By</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
