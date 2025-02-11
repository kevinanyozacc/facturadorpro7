<header class="header">
    <div class="logo-container">
        <div class="sidebar-toggle" data-toggle-class="sidebar-left-collapsed" data-target="html"
             data-fire-event="sidebar-left-toggle">
            <i class="fas fa-angle-left" aria-label="Toggle sidebar"></i>
            <i class="fas fa-angle-right" aria-label="Toggle sidebar"></i>
        </div>
        <div class="d-md-none toggle-sidebar-left" data-toggle-class="sidebar-left-opened" data-target="html" data-fire-event="sidebar-left-opened">
            <i class="fas fa-bars" aria-label="Toggle sidebar"></i>
        </div>
        <tenant-dialog-header-menu></tenant-dialog-header-menu>

        @if ($tenant_show_ads && $url_tenant_image_ads)
            <div class="ml-3 mr-3">
                <img src="{{$url_tenant_image_ads}}" style="max-height: 50px; max-width: 500px;">
            </div>
        @endif

        @if(config('configuration.multi_user_enabled'))
            <tenant-multi-users-change-client></tenant-multi-users-change-client>
        @endif

    </div>
    <div class="header-right">

        <ul class="notifications mx-2">
            @php
                $is_pse = $vc_company->send_document_to_pse;
                $environment = 'SUNAT';
                $is_ose = ($vc_company->soap_send_id === '02')?true:false;
                if($is_pse){
                    $environment = 'PSE';
                }
                if($is_ose) {
                    $environment = 'OSE';
                }
                if($is_ose && $is_pse) {
                    $environment = 'OSE-PSE';
                }
            @endphp
            @if($vc_company->soap_type_id == "01")
                <li>
                    <a href="@if(in_array('configuration', $vc_modules)){{route('tenant.companies.create')}}@else # @endif" class="notification-icon text-secondary" data-toggle="tooltip" data-placement="bottom" title="{{$environment}}: ENTORNO DE DEMOSTRACIÓN, pulse para ir a configuración" style="background-color: transparent !important;">
                        <i class="fas fa-2x fa-toggle-off mr-2" style="font-size: 20px;"></i>
                        <span>DEMO</span>
                    </a>
                </li>
            @elseif($vc_company->soap_type_id == "02")
                <li>
                    <a href="@if(in_array('configuration', $vc_modules)){{route('tenant.companies.create')}}@else # @endif" class="notification-icon text-secondary" data-toggle="tooltip" data-placement="bottom" title="{{$environment}}: ENTORNO DE PRODUCCIÓN, pulse para ir a configuración">
                        <i class="text-success fas fa-2x fa-toggle-on mr-2" style="font-size: 20px; color: #28a745 !important"></i>
                        <span>PROD</span>
                    </a>
                </li>
            @else
                <li>
                    <a href="@if(in_array('configuration', $vc_modules)){{route('tenant.companies.create')}}@else # @endif" class="notification-icon text-secondary" data-toggle="tooltip" data-placement="bottom" title="INTERNO: ENTORNO DE PRODUCCIÓN, pulse para ir a configuración">
                        <i class="text-info fas fa-2x fa-toggle-on mr-2" style="font-size: 20px; color: #398bf7!important;"></i>
                        <span>INT</span>
                    </a>
                </li>
            @endif
        </ul>

        <span class="separator"></span>
        <ul class="notifications">
            <li>
                <a href="{{ route('tenant_orders_index') }}" class="notification-icon text-secondary" data-toggle="tooltip" data-placement="bottom" title="Pedidos pendientes">
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
                    <span class="badge badge-pill badge-info badge-up cart-item-count">{{ $vc_orders }}</span>
                </a>
            </li>
        </ul>

        @if($vc_document > 0)
            <span class="separator"></span>
            <ul class="notifications">
                <li>
                    <a href="{{route('tenant.documents.not_sent')}}" class="notification-icon text-secondary" data-toggle="tooltip" data-placement="bottom" title="Comprobantes no enviados/por enviar">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-bell"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" /><path d="M9 17v1a3 3 0 0 0 6 0v-1" /></svg>
                        <span class="badge badge-pill badge-danger badge-up cart-item-count">{{ $vc_document }}</span>
                    </a>
                </li>
            </ul>
        @endif

        @if($vc_document_regularize_shipping > 0)
            <span class="separator"></span>
            <ul class="notifications">
                <li>
                    <a href="{{route('tenant.documents.regularize_shipping')}}" class="notification-icon text-secondary" data-toggle="tooltip" data-placement="bottom" title="Comprobantes pendientes de rectificación">
                        <i class="fas fa-exclamation-triangle text-secondary"></i>
                        <span class="badge badge-pill badge-danger badge-up cart-item-count">{{ $vc_document_regularize_shipping }}</span>
                    </a>
                </li>
            </ul>
        @endif

        @if( in_array('reports', $vc_modules) && $vc_finished_downloads > 0)
            <span class="separator"></span>
            <ul class="notifications">
                <li>

                    <a href="{{route('tenant.reports.download-tray.index')}}" class="notification-icon text-secondary" data-toggle="tooltip" data-placement="bottom" title="Bandeja de descargas (Reportes procesados)">
                        <i class="fas fa-file-download text-secondary"></i>
                        <span class="badge badge-pill badge-info badge-up cart-item-count">{{ $vc_finished_downloads }}</span>
                    </a>
                </li>
            </ul>
        @endif

        {{-- @if($vc_document > 0 || $vc_document_regularize_shipping > 0 || $vc_finished_downloads > 0)
        <span class="separator"></span>
        <ul class="notifications">
            <li class="showed" id="dropdown-notifications">
                <a href="#" id="dn-toggle" class="dropdown-toggle notification-icon" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="far fa-bell text-secondary"></i>
                    <span class="badge {{ $vc_document == 0 && $vc_document_regularize_shipping == 0 && $vc_finished_downloads > 0 ? 'badge-info' : '' }}">!</span>
                </a>
                <div id="dn-menu" class="dropdown-menu notification-menu" style="">
                    <div class="notification-title"></div>
                    <div class="content">
                        <ul>
                            @if($vc_document > 0)
                            <li>
                                <a href="{{route('tenant.documents.not_sent')}}" class="clearfix">
                                    <div class="image">
                                        <div class="badge badge-pill badge-danger text-light">{{ $vc_document }}</div>
                                    </div>
                                    <span class="title">Comprobantes enviados/por enviar</span>
                                </a>
                            </li>
                            @endif
                            @if($vc_document_regularize_shipping > 0)
                            <li>
                                <a href="{{route('tenant.documents.regularize_shipping')}}" class="clearfix">
                                    <div class="image">
                                        <div class="badge badge-pill badge-warning text-light">
                                            {{ $vc_document_regularize_shipping }}
                                        </div>
                                    </div>
                                    <span class="title">Comprobantes pendientes de rectificación</span>
                                </a>
                            </li>
                            @endif
                            @if($vc_finished_downloads > 0)
                            <li>
                                <a href="{{route('tenant.reports.download-tray.index')}}" class="clearfix">
                                    <div class="image">
                                        <div class="badge badge-pill badge-info text-light">
                                            {{ $vc_finished_downloads }}
                                        </div>
                                    </div>
                                    <span class="title">Bandeja de descargas</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </li>
        </ul>
        @endif --}}

        <span class="separator"></span>
        <div id="userbox" class="userbox">
            <a href="#" data-toggle="dropdown">
                <div class="profile-info" data-lock-name="{{ $vc_user->email }}" data-lock-email="{{ $vc_user->email }}">
                    <span class="name">{{ $vc_user->name }}</span>
                    <span class="role">{{ $vc_user->email }}</span>
                </div>
                <figure class="profile-picture">
                    {{-- <img src="{{asset('img/%21logged-user.jpg')}}" alt="Profile" class="rounded-circle" data-lock-picture="img/%21logged-user.jpg" /> --}}
                    <div class="border rounded-circle text-center" style="width: 25px;">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-user-square-rounded"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 13a3 3 0 1 0 0 -6a3 3 0 0 0 0 6z" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /><path d="M6 20.05v-.05a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v.05" /></svg>
                    </div>
                </figure>
                {{-- <i class="fa custom-caret"></i> --}}
            </a>
            <div class="dropdown-menu">
                <ul class="list-unstyled mb-0">
                    @if(in_array('cuenta', $vc_modules))
                        @if(in_array('account_users_list', $vc_module_levels))
                        <li>
                            <a role="menuitem" href="{{route('tenant.payment.index')}}">
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-receipt-dollar mr-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2" /><path d="M14.8 8a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1" /><path d="M12 6v10" /></svg>
                                <span>Mis Pagos</span>
                            </a>
                        </li>
                        @endif
                    @endif
                    <li>
                        <a class="style-switcher-open" href="#">
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-paint mr-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 3m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" /><path d="M19 6h1a2 2 0 0 1 2 2a5 5 0 0 1 -5 5l-5 0v2" /><path d="M10 15m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" /></svg>
                            Estilos y temas</a>
                    </li>
                    {{-- <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" @click.stop>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-download mr-2">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M4 14v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1 -1v-4" />
                                <path d="M7 10l5 5l5 -5" />
                                <path d="M12 4l0 11" />
                            </svg>
                            Establecimientos <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a class="text-1" href="#" @click.prevent="clickExport()">Reporte recepción</a>
                            </li>
                        </ul>
                    </li> --}}
                    <li class="divider"></li>
                    <li>
                        {{--<a role="menuitem" href="#"><i class="fas fa-user"></i> Perfil</a>--}}
                        <a role="menuitem" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-door-exit mr-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 12v.01" /><path d="M3 21h18" /><path d="M5 21v-16a2 2 0 0 1 2 -2h7.5m2.5 10.5v7.5" /><path d="M14 7h7m-3 -3l3 3l-3 3" /></svg>
                            @lang('app.buttons.logout')
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
{{--
<div class="container d-none d-sm-block">
    <div id="switcher-top" class="d-flex justify-content-center switcher-hover">
        <span class="text-white py-0 px-5 text-center"><i class="fas fa-plus fa-fw"></i>Acceso Rápido</span>
    </div>
</div>
<div class="container d-none d-sm-block">
    <div id="switcher-list" class="d-flex justify-content-center switcher-hover">
        <div class="row">
            <div class="px-3"><a class="py-3" href="{{ route('tenant.documents.create') }}"><i class="fas fa-fw fa-file-invoice" aria-hidden="true"></i> Nuevo Comprobante</a></div>
            <div class="px-3"><a class="py-3" href="{{ in_array('pos', $vc_modules) ? route('tenant.pos.index') : '#' }}"><i class="fas fa-fw fa-cash-register" aria-hidden="true"></i> POS</a></div>
            <div style="min-width: 220px;"></div>
            <div class="px-3"><a class="py-3" href="{{ in_array('configuration', $vc_modules) ? route('tenant.companies.create') : '#' }}"><i class="fas fa-fw fa-industry" aria-hidden="true"></i> Empresa</a></div>
            <div class="px-3"><a class="py-3" href="{{ in_array('establishments', $vc_modules) ? route('tenant.establishments.index') : '#' }}"><i class="fas fa-fw fa-warehouse" aria-hidden="true"></i> Establecimientos</a></div>
        </div>
    </div>
</div> --}}
