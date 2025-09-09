@if (isset($permissions) && count($permissions))
    @foreach ($permissions as $permissionAlias => $permission)
        @if (is_array($permission))
            <section class="permission-section" style="border: 1px solid #ccc; padding: 5px 10px; margin-bottom: 10px;">
                <div class="permission-section-header">
                    <span>
                        <span class="fa fa-chevron-right arrow"></span>
                        {{ __cms($permissionAlias) }}
                    </span>
                    <label class="master-checkbox-label">
                        <a href="#" class="master-label-link"><span class="master-label-text">{{__cms('Выбрать все')}}</span></a>
                    </label>
                </div>
                <div class="permission-section-content">
                    @foreach ($permission as $permissionSlug => $permissionTitle)
                        @if (is_array($permissionTitle))
                            <section style="padding-left: 10px; margin-top: 10px;">
                                <p><strong>{{ __cms($permissionSlug) }}</strong></p>
                                @foreach ($permissionTitle as $permissionSlug2 => $permissionTitle2)
                                    @if (is_array($permissionTitle2))
                                        <section style="padding-left: 10px;">
                                            <p><strong>{{ __cms($permissionSlug2) }}</strong></p>
                                            @foreach ($permissionTitle2 as $permissionLevel2Slug => $permissionLevel2)
                                                <p>
                                                    <label class="checkbox">
                                                        <input type="checkbox" value="true"
                                                               name="permissions[{{ $permissionLevel2Slug }}]"
                                                               @if (isset($groupPermissionsThis[$permissionLevel2Slug]) && $groupPermissionsThis[$permissionLevel2Slug]) checked @endif>
                                                        <i></i> {{ __cms($permissionLevel2) }}
                                                    </label>
                                                </p>
                                            @endforeach
                                        </section>
                                    @else
                                        <p><label class="checkbox">
                                                <input type="checkbox" value="true"
                                                       name="permissions[{{ $permissionSlug2 }}]"
                                                       @if (isset($groupPermissionsThis[$permissionSlug2]) && $groupPermissionsThis[$permissionSlug2]) checked @endif>
                                                <i></i> {{ __cms($permissionTitle2) }}
                                            </label>
                                        </p>
                                    @endif
                                @endforeach
                            </section>
                        @else
                            <p><label class="checkbox">
                                    <input type="checkbox" value="true" name="permissions[{{ $permissionSlug }}]"
                                           @if (isset($groupPermissionsThis[$permissionSlug]) && $groupPermissionsThis[$permissionSlug]) checked @endif>
                                    <i></i> {{ __cms($permissionTitle) }}
                                </label>
                            </p>
                        @endif
                    @endforeach
                </div>
            </section>
        @else
            <section>
                <p><label class="checkbox">
                        <input type="checkbox" value="true" name="permissions[{{ $permissionAlias }}]"
                               @if (isset($groupPermissionsThis[$permissionAlias]) && $groupPermissionsThis[$permissionAlias]) checked @endif>
                        <i></i> {{ __cms($permission) }}
                    </label>
                </p>
            </section>
        @endif
    @endforeach
@endif

<script>
    document.querySelectorAll('.permission-section').forEach(section => {
        const masterLink = section.querySelector('.master-label-link');
        const masterLabelText = section.querySelector('.master-label-text');
        const checkboxes = section.querySelectorAll('.permission-section-content input[type="checkbox"]');

        const updateLabel = () => {
            masterLabelText.textContent = [...checkboxes].every(cb => cb.checked) ? 'Отменить все' : 'Выбрать все';
        };

        updateLabel();

        section.querySelector('.permission-section-header').addEventListener('click', e => {
            if (!e.target.closest('.master-label-link')) section.classList.toggle('open');
        });

        masterLink.addEventListener('click', e => {
            e.preventDefault();
            const allChecked = [...checkboxes].every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            updateLabel();
        });

        checkboxes.forEach(cb => cb.addEventListener('change', updateLabel));
    });
</script>

<style>

    .permission-section {

    }

    .permission-section-content {
        display: none;
        padding-top: 10px;
    }

    .permission-section.open .permission-section-content {
        display: block;
    }

    .permission-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .permission-section-header .arrow {
        transition: transform 0.2s ease-in-out;
        margin-right: 5px;
    }

    .permission-section.open .permission-section-header .arrow {
        transform: rotate(90deg);
    }

    .master-checkbox-label {
        visibility: hidden;
        font-weight: normal;
        margin-bottom: 0;
    }

    .permission-section.open .master-checkbox-label {
        visibility: visible;
    }
</style>
