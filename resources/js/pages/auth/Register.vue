<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});

</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="full_name">Full Name</Label>
                <Input
                    id="full_name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="full_name"
                    placeholder="Full name"
                />
                <InputError :message="errors.full_name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone_number">Phone Number</Label>
                <Input
                    id="phone_number"
                    type="text"
                    required
                    :tabindex="3"
                    autocomplete="tel"
                    name="phone_number"
                    placeholder="e.g., 012-3456789"
                />
                <InputError :message="errors.phone_number" />
            </div>

            <div class="grid gap-2">
                <Label for="address">Home Address</Label>
                <Input
                    id="address"
                    type="text"
                    required
                    :tabindex="4"
                    autocomplete="street-address"
                    name="address"
                    placeholder="123 Workshop Street"
                />
                <InputError :message="errors.address" />
            </div>

            <div class="grid gap-2">
                <Label for="position">Assigned Position</Label>
                <Input
                    id="position"
                    type="text"
                    required
                    :tabindex="5"
                    name="position"
                    placeholder="e.g., Mechanic, Admin, Service Advisor"
                />
                <InputError :message="errors.position" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="6"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="7"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="8"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="9"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>