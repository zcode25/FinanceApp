<div>
    <x-filament::modal id="admin-profile-modal" width="2xl">
        <x-slot name="heading">
            Admin Profile Settings
        </x-slot>

        <form wire:submit.prevent="save">
            {{ $this->form }}

            <div class="flex items-center justify-end gap-x-3 pt-6">
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'admin-profile-modal' })">
                    Cancel
                </x-filament::button>

                <x-filament::button type="submit" color="primary">
                    Save Changes
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>
</div>