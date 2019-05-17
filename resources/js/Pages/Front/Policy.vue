<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
</script>

<template>
    <Head title="Policy" />

    <AppLayout>
        <div class="bg-primary relative">
            <img :src="asset('assets/img/hero_1.png')" alt="" class="absolute top-0 left-0">

            <div class="mx-auto max-w-8xl px-6 py-16 sm:py-32 text-center">
                <div class="uppercase py-1 leading-6 text-primary font-erica-one text-3xl">
                    Privacy Policy
                </div>
            </div>
        </div>
        <div class="bg-white min-h-[20vh]">
            <div class="py-2 mx-auto max-w-2xl py-8">
                <div class="space-y-4">
                    <p class=" mb-4">

                        Last updated: June 28, 2023 </p><p>

                </p><h3>1. Introduction</h3>
                    <p>
                        Gang Sheet Builder ("we", "us", "our") operates the Gang Sheet Builder App ("the app"), which allows customers to upload their own images onto an artboard and enables a print company to access the image for printing purposes. We are committed to protecting and respecting your privacy. This Privacy Policy explains the types of information we collect, how we use it, how it is shared, and your rights regarding that information.
                    </p><h3>
                    2. Information We Collect
                </h3><p>
                    2.1. User Provided Information: We collect information that you provide directly to us when using our app. This includes, but is not limited to: name, email address, and images you upload.
                </p><p>
                    2.2. Automatically Collected Information: When you use our app, we may automatically collect certain information about your device and usage of the app. This includes your device's Internet Protocol (IP) address, device and browser type, and the date and time of your usage.
                </p><h3>
                    3. How We Use Your Information
                </h3><p>
                    3.1. We use the information we collect to provide, maintain, and improve our app. This includes using your images to enable the artboard design functionality and allowing print companies to print your designs.
                </p><p>
                    3.2. We also use the information to respond to your comments, questions, and requests, and to send you technical notices, updates, and support messages.
                </p><h3>
                    4. Sharing Your Information
                </h3><p>
                    4.1. Print Companies: Your images will be shared with print companies to fulfill your print requests. These companies are contractually obliged to keep your information confidential and to use it only for the purposes of printing your designs.
                </p><p>
                    4.2. Legal Requirements and Policy Enforcement: We may disclose your information if required by law, if we believe that disclosure is necessary to comply with a legal obligation, or if we believe that your actions violate our Terms of Service.
                </p><h3>
                    5. Your Rights
                </h3><p>
                    5.1. You have the right to request access to, correction of, or deletion of your personal information.
                </p><p></p>
                    5.2. You can object to the processing of your personal information, ask us to restrict processing of your personal information, or request portability of your personal information.
                    <p></p><h3>
                    6. Changes to This Policy
                </h3><p>
                    We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page. You are advised to review this Privacy Policy periodically for any changes.
                </p><h3>
                    7. Contact Us
                </h3><p>
                    If you have any questions about this Privacy Policy, please contact us at: <a href="mailto:admin@rdyprinting.com" class="text-primary">admin@rdyprinting.com</a>.
                </p><p>
                    By using the Gang Sheet Builder app, you consent to our collection, use, and sharing of your information as described in this Privacy Policy.

                </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
