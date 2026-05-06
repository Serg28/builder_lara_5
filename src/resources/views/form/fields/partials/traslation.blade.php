{{--
<script>
    @if ($field->getTraslationField())

		var runTrans = true;

		@if ($field->getTraslationOnlyEmpty() == true)
			runTrans = $('[data-name-input={{$definition->getNameDefinition().$field->getNameField()}}]').val() == '' ? true : false;
		@endif

		if (runTrans) {
            $('[data-name-input={{$definition->getNameDefinition().$field->getNameField()}}]').keyup(function(){
                $('[data-name-input={{ $definition->getNameDefinition().$field->getTraslationField() }}]').val(TableBuilder.urlRusLat($(this).val()));
            });
		}
    @endif
</script>
--}}

<script>
    @if ($field->getTraslationField())

    let runTrans = true;

    @if ($field->getTraslationOnlyEmpty() === true)
        runTrans = $('[data-name-input={{ $definition->getNameDefinition().$field->getNameField() }}]').val() === '';
    @endif

    if (runTrans) {
        const source = $('[data-name-input={{ $definition->getNameDefinition().$field->getNameField() }}]');
        const target = $('[data-name-input={{ $definition->getNameDefinition().$field->getTraslationField() }}]');

        let timeout = null;
        const handlerClass = @json(get_class($field)); // ✅ FQCN з обʼєкта

        source.on('keyup', function () {
            clearTimeout(timeout);

            const value = $(this).val();
            if (!value) return;

            timeout = setTimeout(() => {
                $.ajax({
                    url: '{{ route('cms.slugify') }}',
                    method: 'POST',
                    data: {
                        text: value,
                        handler: handlerClass,
                        _token: '{{ csrf_token() }}'
                    },
                    success(res) {
                        if (res.slug) {
                            target.val(res.slug);
                        }
                    }
                });
            }, 400);
        });
    }
    @endif
</script>