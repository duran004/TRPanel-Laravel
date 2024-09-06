<form action="{{ route('filemanager.file.permissions_update') }}" method="post" class="formajax_refresh_popup">
    @csrf
    <x-input name="_files" type="hidden" :value="$files_str" />
    <select name="permissions" id="permissions"
        class="bg-white border border-gray-300 rounded py-2 px-4 block w-full appearance-none leading-normal" required>
        <option value="777" selected>777 (rwxrwxrwx)</option>
        <option value="755">755 (rwxr-xr-x)</option>
        <option value="700">700 (rwx------)</option>
        <option value="644">644 (rw-r--r--)</option>
        <option value="600">600 (rw-------)</option>
        <option value="444">444 (r--r--r--)</option>
        <option value="440">440 (r--r-----)</option>
        <option value="400">400 (r--------)</option>
        <option value="222">222 (w--w--w--)</option>
        <option value="220">220 (w--w-----)</option>
        <option value="200">200 (w-------)</option>
        <option value="111">111 (--x--x--x)</option>
        <option value="110">110 (--x--x---)</option>
        <option value="100">100 (--x------)</option>
    </select>
    <x-button type="submit" class="mt-4 w-full">
        {{ __('Update') }}
    </x-button>
</form>


<p>{{ __('Files to be affected') }}</p>
@foreach ($files as $file)
    <p>{{ $file }}</p>
@endforeach
