@extends('tenant.layouts.app')

@section('content')

    @php
        // Para mostrar los botones de reporte a todos los usuarios,
        // pasamos el tipo real solo si NO es 'seller'; los sellers
        // reciben 'user' para que vean los reportes pero no Editar/Eliminar.
        $cashUserType = (Auth::user()->type === 'seller') ? 'user' : Auth::user()->type;
    @endphp

    <cash-index :type-user="{{ json_encode($cashUserType) }}"></cash-index>

@endsection
