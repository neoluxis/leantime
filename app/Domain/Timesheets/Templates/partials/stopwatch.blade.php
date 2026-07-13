@if ($login::userIsAtLeast(\Leantime\Domain\Auth\Models\Roles::$editor, true))

    <li class='timerHeadMenu' id='timerHeadMenu' hx-get="{{BASE_URL}}/timesheets/stopwatch/get-status" hx-trigger="timerUpdate from:body{{ $onTheClock !== false ? ', every 60s' : '' }}" hx-swap="outerHTML">

    @if ($onTheClock !== false)
        @php
            $headline = substr($onTheClock['headline'], 0, 10);
            $timerMarkup = sprintf(
                __('text.timer_on_todo'),
                '<span class="timer-head-value">' . $onTheClock['totalTime'] . '</span>',
                e($headline)
            );
        @endphp
            <a
                href='javascript:void(0);'
                class='dropdown-toggle'
                data-toggle='dropdown'
                data-timer-since="{{ (int) $onTheClock['since'] }}"
            >{!! $timerMarkup !!}</a>

            <ul class="dropdown-menu">
                <li>
                    <a href="#/tickets/showTicket/{{ $onTheClock['id'] }}">
                        {!! __('links.view_todo') !!}
                    </a>
                </li>
                <li>
                    <a
                        href="javascript:void(0);"
                        class="punchOut"
                        hx-patch="{{ BASE_URL }}/hx/timesheets/stopwatch/stop-timer/"
                        hx-target="#timerHeadMenu"
                        hx-vals='{"ticketId": "{{ $onTheClock['id']  }}", "action":"stop"}'
                        hx-swap="outerHTML"
                    >{!! __('links.stop_timer') !!}</a>
                </li>
            </ul>
    @endif
    </li>

    <script>
        (function () {
            function pad(value) {
                return String(value).padStart(2, '0');
            }

            function renderStopwatchHeader() {
                var timerLink = document.querySelector('#timerHeadMenu .dropdown-toggle[data-timer-since]');

                if (!timerLink) {
                    if (window.leantimeStopwatchTicker) {
                        window.clearInterval(window.leantimeStopwatchTicker);
                        window.leantimeStopwatchTicker = null;
                    }
                    return;
                }

                var timerValue = timerLink.querySelector('.timer-head-value');
                if (!timerValue) {
                    return;
                }

                var since = parseInt(timerLink.getAttribute('data-timer-since'), 10);
                if (!since) {
                    return;
                }

                var elapsed = Math.max(0, Math.floor(Date.now() / 1000) - since);
                var hours = Math.floor(elapsed / 3600);
                var minutes = Math.floor((elapsed % 3600) / 60);
                var seconds = elapsed % 60;

                timerValue.textContent = pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
            }

            function startStopwatchHeader() {
                if (window.leantimeStopwatchTicker) {
                    window.clearInterval(window.leantimeStopwatchTicker);
                }

                renderStopwatchHeader();

                if (document.querySelector('#timerHeadMenu .dropdown-toggle[data-timer-since]')) {
                    window.leantimeStopwatchTicker = window.setInterval(renderStopwatchHeader, 1000);
                } else {
                    window.leantimeStopwatchTicker = null;
                }
            }

            if (!window.leantimeStopwatchHeaderBound) {
                window.leantimeStopwatchHeaderBound = true;

                document.addEventListener('DOMContentLoaded', startStopwatchHeader);
                document.body.addEventListener('htmx:afterSwap', function (event) {
                    if (!event.target) {
                        return;
                    }

                    if (event.target.id === 'timerHeadMenu' || event.target.querySelector('#timerHeadMenu')) {
                        startStopwatchHeader();
                    }
                });
            }

            window.leantimeInitStopwatchHeader = startStopwatchHeader;
            startStopwatchHeader();
        })();
    </script>
@endif
