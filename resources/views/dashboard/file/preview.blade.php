<form method="POST" action="{{ route('filemanager.file.preview_update') }}" class="formajax_popup"
    enctype="multipart/form-data">
    @csrf
    <div class="relative">
        <x-textarea is="highlighted-code" cols="3" rows="1" language="php" tab-size="2" name="content"
            class="h-full" id="code">{{ $content }}</x-textarea>
        <script type="module" defer>
            (async ({
                chrome,
                netscape
            }) => {
                if (!chrome && !netscape) await import('https://unpkg.com/@ungap/custom-elements');
                const {
                    default: HighlightedCode
                } = await import('https://unpkg.com/highlighted-code');
                HighlightedCode.useTheme('github');

            })(self);
        </script>

    </div>

    <x-input type="text" class="form-control" name="path" value="{{ $path }}" hidden />
    <x-input type="text" class="form-control" name="file" value="{{ $file }}" hidden />
    <x-button type="submit" class="btn-primary">{{ __('Update') }}</x-button>
</form>
