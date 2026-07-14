@extends('admin.layout')

@section('content')
    <div class="max-w-lg mx-auto text-center">
        <h2 class="text-2xl font-semibold mb-4">QR Code - {{ $device->serial_number }}</h2>

        <div class="rounded-xl bg-white border border-slate-200 p-8">
            <div class="mb-4">
                {!! $qrSvg !!}
            </div>
            <p class="text-sm text-slate-500 mb-1">Serial Number: <strong>{{ $device->serial_number }}</strong></p>
            <p class="text-sm text-slate-500 mb-1">Model: <strong>{{ $device->model ?? '-' }}</strong></p>
            <p class="text-sm text-slate-500 mb-4">Data: <code class="bg-slate-100 px-2 py-0.5 rounded text-xs">{{ $qrCodeData }}</code></p>

            <div class="flex gap-3 justify-center">
                <button onclick="window.print()" class="rounded bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2">Print</button>
                <a href="{{ route('admin.devices.index') }}" class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2">Back</a>
            </div>
        </div>
    </div>
@endsection