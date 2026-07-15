@extends('admin.layout')

@section('content')
    <h2 class="text-2xl font-semibold">Test FCM Notification</h2>

    <div class="rounded-xl bg-white border border-slate-200 p-6 mt-6 max-w-2xl">
        <p class="text-sm text-slate-600 mb-5">
            Send a test push notification to nurses' Android devices via Firebase Cloud Messaging (FCM).
            Registered tokens: <strong>{{ $totalTokens }}</strong>
        </p>

        <form method="POST" action="{{ route('admin.test-fcm.send') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1" for="target">Target</label>
                <select class="w-full rounded border border-slate-300 px-3 py-2" name="target" id="target" onchange="toggleDeviceSelect()">
                    <option value="all_tokens" @selected(old('target') === 'all_tokens')>All registered FCM tokens</option>
                    <option value="device_subscribers" @selected(old('target') === 'device_subscribers')>Subscribers of a specific device</option>
                </select>
            </div>

            <div class="mb-4" id="device-select-wrapper" @if (old('target') !== 'device_subscribers') style="display:none" @endif>
                <label class="block text-sm font-medium mb-1" for="device_id">Device</label>
                <select class="w-full rounded border border-slate-300 px-3 py-2" name="device_id" id="device_id">
                    <option value="">-- Select device --</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected((int) old('device_id') === $device->id)>{{ $device->serial_number }}</option>
                    @endforeach
                </select>
                @error('device_id')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1" for="title">Notification Title</label>
                <input class="w-full rounded border border-slate-300 px-3 py-2" type="text" name="title" id="title" value="{{ old('title', 'Test notification from admin') }}" required maxlength="255">
                @error('title')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1" for="body">Notification Body</label>
                <textarea class="w-full rounded border border-slate-300 px-3 py-2" name="body" id="body" rows="3" required maxlength="1000">{{ old('body', 'This is a test push notification from the Smart Infus admin panel.') }}</textarea>
                @error('body')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="alert_type">Alert Type (optional)</label>
                    <input class="w-full rounded border border-slate-300 px-3 py-2" type="text" name="alert_type" id="alert_type" value="{{ old('alert_type') }}" placeholder="e.g. occlusion, near_empty" maxlength="50">
                    <p class="text-xs text-slate-500 mt-1">Will be included in the data payload</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="severity">Severity (optional)</label>
                    <select class="w-full rounded border border-slate-300 px-3 py-2" name="severity" id="severity">
                        <option value="">-- None --</option>
                        @foreach (['info', 'warning', 'critical'] as $opt)
                            <option value="{{ $opt }}" @selected(old('severity') === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Will be included in the data payload</p>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2" type="submit">Send Test Notification</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-5 py-2" href="{{ route('admin.test-fcm.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-6 mt-6 max-w-2xl">
        <h3 class="font-semibold mb-3">What Gets Sent</h3>
        <p class="text-sm text-slate-600 mb-3">
            The backend sends an FCM v1 API message with <strong>both</strong> <code>notification</code> and <code>data</code> blocks,
            plus <code>android.priority = high</code>. This ensures the notification appears on the device in all app states
            (foreground, background, killed).
        </p>
        <pre class="bg-slate-900 text-slate-100 text-xs rounded p-4 overflow-x-auto"><code>{
  "message": {
    "token": "[FCM_TOKEN]",
    "notification": {
      "title": "{{ old('title', 'Test notification from admin') }}",
      "body": "{{ old('body', 'This is a test push notification...') }}"
    },
    "data": {
      @if (old('alert_type'))
      "alert_type": "{{ old('alert_type') }}",
      @endif
      @if (old('severity'))
      "severity": "{{ old('severity') }}",
      @endif
      "sender": "{{ auth()->user()->name }}",
      "organization_id": "{{ auth()->user()->organization_id }}"
    },
    "android": {
      "priority": "high"
    }
  }
}</code></pre>
    </div>

    <script>
        function toggleDeviceSelect() {
            const target = document.getElementById('target').value;
            const wrapper = document.getElementById('device-select-wrapper');
            wrapper.style.display = target === 'device_subscribers' ? 'block' : 'none';
        }
    </script>
@endsection