<!DOCTYPE html>
@php
    $path = explode('/', request()->path());
    $path[1] = (array_key_exists(1, $path)> 0)?$path[1]:'';
    $path[2] = (array_key_exists(2, $path)> 0)?$path[2]:'';
    $path[0] = ($path[0] === '')?'documents':$path[0];
    $visual->sidebar_theme = property_exists($visual, 'sidebar_theme')?$visual->sidebar_theme:''
@endphp
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="fixed no-mobile-device custom-scroll
        sidebar-{{$visual->sidebar_theme ?? ''}}
        {{ ($visual->sidebar_theme == 'white'
        || $visual->sidebar_theme == 'gray'
        || $visual->sidebar_theme == 'green'
        || $visual->sidebar_theme == 'warning'
        || $visual->sidebar_theme == 'ligth-blue') ? 'sidebar-light' : '' }}
        {{$vc_compact_sidebar->compact_sidebar == true
        || $path[0] === 'pos'
        || $path[0] === 'pos' && $path[1] === 'fast'
        || $path[0] === 'documents' && $path[1] === 'create' ? 'sidebar-left-collapsed' : ''}}
        {{-- header-{{$visual->navbar ?? 'fixed'}} --}}
        {{-- {{$visual->header == 'dark' ? 'header-dark' : ''}} --}}
        {{-- {{$visual->sidebars == 'dark' ? '' : 'sidebar-light'}} --}}
        {{$visual->bg == 'dark' ? 'dark' : ''}}
        {{ ($path[0] === 'documents' && $path[1] === 'create'
        || $path[0] === 'documents' && $path[1] === 'note'
        || $path[0] === 'quotations' && $path[1] === 'create'
        || $path[0] === 'sale-opportunities' && $path[1] === 'create'
        || $path[0] === 'order-notes' && $path[1] === 'create'
        || $path[0] === 'sale-notes' && $path[1] === 'create'
        || $path[0] === 'purchase-quotations' && $path[1] === 'create'
        || $path[0] === 'purchase-orders' && $path[1] === 'create'
        || $path[0] === 'dispatches' && $path[1] === 'create'
        || $path[0] === 'purchases' && $path[1] === 'create') ? 'newinvoice' : ''}}
        ">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $vc_company->title_web }}</title>
    <meta name="googlebot" content="noindex">
    <meta name="robots" content="noindex">

    <link async href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('porto-light/vendor/bootstrap/css/bootstrap.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/animate/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/font-awesome/5.11/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/meteocons/css/meteocons.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/select2/css/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/select2-bootstrap-theme/select2-bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/datatables/media/css/dataTables.bootstrap4.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.26.29/sweetalert2.min.css" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.css')}}" />

    <link rel="stylesheet" href="{{asset('porto-light/vendor/jquery-ui/jquery-ui.css')}}" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/jquery-ui/jquery-ui.theme.css')}}" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/select2/css/select2.css')}}" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/select2-bootstrap-theme/select2-bootstrap.min.css')}}" />

    <link href="{{ asset('porto-light/vendor/bootstrap-timepicker/css/bootstrap-timepicker.css') }}" rel="stylesheet">
    <link href="{{ asset('porto-light/vendor/bootstrap-daterangepicker/daterangepicker.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{asset('porto-light/vendor/bootstrap-timepicker/css/bootstrap-timepicker.css')}}" />

    <link rel="stylesheet" href="{{asset('porto-light/vendor/jquery-loading/dist/jquery.loading.css')}}" />

    <link rel="stylesheet" type="text/css" href="{{ asset('porto-light/master/style-switcher/style-switcher.css')}}">

    <link rel="stylesheet" href="{{ asset('porto-light/css/theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/css/custom.css') }}" />

    @if (file_exists(public_path('theme/custom_styles.css')))
        <link rel="stylesheet" href="{{ asset('theme/custom_styles.css') }}" />
    @endif

    @if($vc_compact_sidebar->skin)
        @if (file_exists(storage_path('app/public/skins/'.$vc_compact_sidebar->skin->filename)))
            <link rel="stylesheet" href="{{ asset('storage/skins/'.$vc_compact_sidebar->skin->filename) }}" />
        @endif
    @endif


    @stack('styles')


    <script src="{{ asset('porto-light/vendor/modernizr/modernizr.js') }}"></script>

    <style>
        .descarga {
            color:black;
            padding:5px;
        }
        .el-checkbox__label {
            font-size: 13px;
        }
        .center-el-checkbox {
            display: flex;
            align-items: center;
        }
        .center-el-checkbox .el-checkbox {
            margin-bottom: 0
        }

    </style>

    @if ($vc_company->favicon)
        <link rel="shortcut icon" type="image/png" href="{{ asset($vc_company->favicon) }}"/>
    @endif
    <script defer src="{{ mix('js/app.js') }}"></script>

<script async src="https://social.buho.la/pixel/y9nonmie9j8dkwha20ct2ua7nwsywi2m"></script>
<script>
    (function() {
        const savedTheme = localStorage.getItem('selectedTheme');
        if (savedTheme) {
            const themes = {
                white: { 
                    "--color-visual": "#0c7286",
                    "--primary-color": "#0c7286",
                    "--dark-color": "#001524",
                    "--light-color": "#eef5f5",
                    "--light2-color": "#d5e8e8",
                    "--light3-color": "#75e4e4"
                },
                blue: {
                    "--color-visual": "#7367f0",
                    "--primary-color": "#7367f0",
                    "--dark-color": "#1a1b4b",
                    "--light-color": "#e3e3ff",
                    "--light2-color": "#c0c0ff",
                    "--light3-color": "#9090ff"
                },
                green: {
                    "--color-visual": "#28c76f",
                    "--primary-color": "#28c76f",
                    "--dark-color": "#0a3d27",
                    "--light-color": "#d9f5e3",
                    "--light2-color": "#a8e6cb",
                    "--light3-color": "#75c2a2"
                },
                red: {
                    "--color-visual": "#ea5455",
                    "--primary-color": "#ea5455",
                    "--dark-color": "#520000",
                    "--light-color": "#fddddd",
                    "--light2-color": "#f8a6a6",
                    "--light3-color": "#f37171"
                },
                retro: {
                    "--color-visual": "#ece3ca",
                    "--primary-color": "#2e282a",
                    "--dark-color": "#282425",
                    "--light-color": "#ece3ca",
                    "--light2-color": "#e4d8b4",
                    "--light3-color": "#e0c881"
                }
            };

            if (themes[savedTheme]) {
                Object.keys(themes[savedTheme]).forEach(variable => {
                    document.documentElement.style.setProperty(variable, themes[savedTheme][variable]);
                });
            }
        }
    })();
</script>
</head>
<body class="pr-0">
    <section class="body">
        <!-- start: header -->
        {{-- @include('tenant.layouts.partials.header') --}}
        <!-- end: header -->
        <div class="inner-wrapper">
            <!-- start: sidebar -->
            @include('tenant.layouts.partials.sidebar')
            <!-- end: sidebar -->
            <section role="main" class="content-body" id="main-wrapper">
                @include('tenant.layouts.partials.header')
              @yield('content')
              @include('tenant.layouts.partials.sidebar_styles')

              @include('tenant.layouts.partials.check_last_password_update')

            </section>

            @yield('package-contents')
        </div>
    </section>
    @if($show_ws)
        @if(strlen($phone_whatsapp) > 0)
        <a class='ws-flotante d-flex align-items-center justify-content-center' href='https://wa.me/{{$phone_whatsapp}}' target="BLANK" 
            style="font-size: 45px; color: #fff; background-color: #0074ff; text-decoration: none; border-radius: 30% !important;">
            <i class="fab fa-whatsapp"></i>
        </a>
        @endif
    @endif


    <!-- Vendor -->
    <script src="{{ asset('porto-light/vendor/jquery/jquery.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/jquery-browser-mobile/jquery.browser.mobile.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/jquery-cookie/jquery-cookie.js')}}"></script>
    {{-- <script src="{{ asset('porto-light/master/style-switcher/style.switcher.js')}}"></script> --}}
    <script src="{{ asset('porto-light/vendor/popper/umd/popper.min.js')}}"></script>
    <!-- <script src="{{ asset('porto-light/vendor/bootstrap/js/bootstrap.js')}}"></script> -->
    {{-- <script src="{{ asset('porto-light/vendor/common/common.js')}}"></script> --}}
    <script src="{{ asset('porto-light/vendor/nanoscroller/nanoscroller.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/magnific-popup/jquery.magnific-popup.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/jquery-placeholder/jquery-placeholder.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/select2/js/select2.js') }}"></script>
    <script src="{{ asset('porto-light/vendor/datatables/media/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/datatables/media/js/dataTables.bootstrap4.min.js')}}"></script>

    {{-- Specific Page Vendor --}}
    <script src="{{asset('porto-light/vendor/jquery-ui/jquery-ui.js')}}"></script>
    <script src="{{asset('porto-light/vendor/jqueryui-touch-punch/jqueryui-touch-punch.js')}}"></script>
    <!--<script src="{{asset('porto-light/vendor/select2/js/select2.js')}}"></script>-->

    <script src="{{asset('porto-light/vendor/jquery-loading/dist/jquery.loading.js')}}"></script>

    <!--<script src="assets/vendor/select2/js/select2.js"></script>-->
    {{--<script src="{{asset('porto-light/vendor/bootstrap-multiselect/bootstrap-multiselect.js')}}"></script>--}}

    <!-- Moment -->
    {{--<script src="{{ asset('porto-light/vendor/moment/moment.js') }}"></script>--}}

    <!-- DatePicker -->
    {{--<script src="{{asset('porto-light/vendor/bootstrap-datepicker/js/bootstrap-datepicker.js')}}"></script>--}}

    <!-- Date range Plugin JavaScript -->
    {{--<script src="{{ asset('porto-light/vendor/bootstrap-timepicker/bootstrap-timepicker.js') }}"></script>--}}
    {{--<script src="{{ asset('porto-light/vendor/bootstrap-daterangepicker/daterangepicker.js') }}"></script>--}}

    <!-- Theme Initialization Files -->
    {{-- <script src="{{asset('porto-light/js/theme.init.js')}}"></script> --}}

    {{--<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>--}}
    {{--<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>--}}

    @stack('scripts')

    <script src="{{ asset('js/manifest.js') }}"></script>
    <script src="{{ asset('js/vendor.js') }}"></script>
    <!-- Theme Base, Components and Settings -->
    <script src="{{asset('porto-light/js/theme.js')}}"></script>

    <!-- Theme Custom -->
    <script src="{{asset('porto-light/js/custom.js')}}"></script>
    <script src="{{asset('porto-light/js/jquery.xml2json.js')}}"></script>

    <script>

        function parseXMLToJSON(source)
        {
            let transform = $.xml2json(source);
            return transform
        }

        $(document).ready(function () {
            $('#dropdown-notifications').click(function(e) {
                $('#dropdown-notifications').toggleClass('showed');
                $('#dn-toggle').toggleClass('show');
                $('#dn-menu').toggleClass('show');
                e.stopPropagation();
            });
        });

        $(document).click(function(){
            $('#dropdown-notifications').removeClass('showed');
            $('#dn-toggle').removeClass('show');
            $('#dn-menu').removeClass('show');
        });

    </script>
    <!-- <script src="//code.tidio.co/1vliqewz9v7tfosw5wxiktpkgblrws5w.js"></script> -->
</body>
</html>
