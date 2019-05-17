<script setup>
import { Head } from '@inertiajs/vue3';
import SvgIcon from '@jamescoyle/vue-icon';
import { mdiCheck } from '@mdi/js';
</script>

<template>
    <div class="w-full h-full bg-[#e5dfeb] pt-16">
        <Head title="Reset Password" />
        <div class="max-w-[430px] mx-auto text-center">
            <div class="bg-white rounded-lg p-8 shadow-xl">
                <div class="mb-6">
                    <div class="w-16 h-16 mx-auto rounded-full bg-green-500 flex items-center justify-center">
                        <SvgIcon 
                            type="mdi" 
                            :path="mdiCheck"
                            class="w-10 h-10 text-white"
                        />
                    </div>
                </div>
                <h2 class="text-xl font-semibold mb-2">Password Changed!</h2>
                <p class="text-gray-600">Your password has been changed successfully.</p>
            </div>
        </div>
    </div>
</template>