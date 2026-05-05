@csrf
<div class="card-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Item Name</label>
            <input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="item_category_id" class="form-label">Category</label>
            <select id="item_category_id" name="item_category_id" class="form-select @error('item_category_id') is-invalid @enderror" required>
                <option value="">Select category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $category->id === old('item_category_id'))>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('item_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="{{ $dateField }}" class="form-label">{{ $dateLabel }}</label>
            <input id="{{ $dateField }}" type="date" name="{{ $dateField }}" value="{{ old($dateField, now()->toDateString()) }}" class="form-control @error($dateField) is-invalid @enderror" required>
            @error($dateField)<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-12">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="color" class="form-label">Color</label>
            <input id="color" name="color" value="{{ old('color') }}" class="form-control @error('color') is-invalid @enderror">
            @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="brand_model" class="form-label">Brand / Model</label>
            <input id="brand_model" name="brand_model" value="{{ old('brand_model') }}" class="form-control @error('brand_model') is-invalid @enderror">
            @error('brand_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="serial_imei" class="form-label">Serial / IMEI</label>
            <input id="serial_imei" name="serial_imei" value="{{ old('serial_imei') }}" class="form-control @error('serial_imei') is-invalid @enderror">
            @error('serial_imei')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="attachments" class="form-label">Images / Evidence</label>
            <input id="attachments" type="file" name="attachments[]" class="form-control @error('attachments.*') is-invalid @enderror" multiple accept=".jpg,.jpeg,.png,.pdf">
            @error('attachments.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="campus_id" class="form-label">Campus</label>
            <select id="campus_id" name="campus_id" class="form-select @error('campus_id') is-invalid @enderror" required>
                <option value="">Select campus</option>
                @foreach ($campuses as $campus)
                    <option value="{{ $campus->id }}" @selected((string) $campus->id === old('campus_id'))>{{ $campus->name }}</option>
                @endforeach
            </select>
            @error('campus_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="building_id" class="form-label">Building</label>
            <select id="building_id" name="building_id" class="form-select @error('building_id') is-invalid @enderror">
                <option value="">Manual / outdoor location</option>
                @foreach ($buildings as $building)
                    <option value="{{ $building->id }}" @selected((string) $building->id === old('building_id'))>{{ $building->name }} - {{ $building->campus->name }}</option>
                @endforeach
            </select>
            @error('building_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">GPS Location</label>
            <div class="border rounded p-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="location_capture_option" id="location_skip" value="skip" checked>
                    <label class="form-check-label" for="location_skip">Do not include GPS coordinates</label>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="radio" name="location_capture_option" id="location_capture" value="capture">
                    <label class="form-check-label" for="location_capture">Capture my current location</label>
                </div>
                <button type="button" id="capture-location-button" class="btn btn-outline-primary btn-sm mt-3">Capture Location</button>
                <div id="location-status" class="form-text">Campus and building are still used for matching when GPS is skipped.</div>
            </div>
        </div>
        <div class="col-md-2">
            <label for="latitude" class="form-label">Latitude</label>
            <input id="latitude" name="latitude" value="{{ old('latitude') }}" class="form-control @error('latitude') is-invalid @enderror" readonly>
            @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label for="longitude" class="form-label">Longitude</label>
            <input id="longitude" name="longitude" value="{{ old('longitude') }}" class="form-control @error('longitude') is-invalid @enderror" readonly>
            @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
<div class="card-footer d-flex justify-content-end gap-2">
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-success">{{ $submitLabel }}</button>
</div>

@push('scripts')
    <script>
        const captureButton = document.getElementById('capture-location-button');
        const captureOption = document.getElementById('location_capture');
        const skipOption = document.getElementById('location_skip');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const statusElement = document.getElementById('location-status');

        captureButton?.addEventListener('click', () => {
            captureOption.checked = true;

            if (!navigator.geolocation) {
                statusElement.textContent = 'Your browser does not support location capture. You can submit without GPS.';
                return;
            }

            statusElement.textContent = 'Requesting location permission...';

            navigator.geolocation.getCurrentPosition((position) => {
                latitudeInput.value = position.coords.latitude.toFixed(7);
                longitudeInput.value = position.coords.longitude.toFixed(7);
                statusElement.textContent = 'Location captured. You can submit the report now.';
            }, () => {
                statusElement.textContent = 'Location permission was denied or unavailable. You can submit without GPS.';
                skipOption.checked = true;
                latitudeInput.value = '';
                longitudeInput.value = '';
            }, {
                enableHighAccuracy: true,
                maximumAge: 60000,
                timeout: 10000,
            });
        });

        skipOption?.addEventListener('change', () => {
            if (skipOption.checked) {
                latitudeInput.value = '';
                longitudeInput.value = '';
                statusElement.textContent = 'GPS skipped. Campus, building, and item details will be used for matching.';
            }
        });
    </script>
@endpush
