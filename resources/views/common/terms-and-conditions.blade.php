<x-layouts.common :title="__('Terms and Conditions')">
    <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
        <span class="flex items-center justify-center">
            <x-app-logo-icon class="size-28 fill-current rounded-full text-black dark:text-white" />
        </span>
    </a>

    <div class="max-w-4xl mx-auto px-6 py-12">
        <h1 class="text-3xl font-bold text-primary mb-6">Terms and Conditions</h1>

        <p class="text-neutral-600 dark:text-neutral-400 mb-4">
            Welcome to <strong>Smart Rental Property Management</strong> (also known as <strong>SRPM</strong>).
            These Terms and Conditions outline the rules and regulations for the use of our system.
            By accessing or using this platform, you agree to be bound by these terms.
        </p>

        <h2 class="text-2xl font-semibold text-neutral-800 dark:text-neutral-200 mt-8 mb-3">1. Acceptance of Terms</h2>
        <p class="text-neutral-600 dark:text-neutral-400 mb-4">
            By creating an account or using this system, you acknowledge that you have read, understood,
            and agree to comply with these Terms and Conditions. If you do not agree, please do not use the system.
        </p>

        <h2 class="text-2xl font-semibold text-neutral-800 dark:text-neutral-200 mt-8 mb-3">2. User Roles and Responsibilities</h2>
        <p class="text-neutral-600 dark:text-neutral-400 mb-4">
            The system is intended for property owners and tenants. Each user is responsible for maintaining
            accurate account information and ensuring that their login credentials remain confidential.
        </p>

        <ul class="list-disc list-inside text-neutral-600 dark:text-neutral-400 mb-4">
            <li><strong>Owners</strong> may manage buildings, units, leases, payments, and maintenance requests.</li>
            <li><strong>Tenants</strong> may access their lease details, pay rent, and request maintenance services.</li>
        </ul>

        <h2 class="text-2xl font-semibold text-neutral-800 dark:text-neutral-200 mt-8 mb-3">3. Payments and Transactions</h2>
        <p class="text-neutral-600 dark:text-neutral-400 mb-4">
            All payment transactions made through the platform (e.g., GCash, bank transfer, e-wallet)
            are subject to third-party payment processor terms. The system records and tracks payment history
            but does not store sensitive financial information.
        </p>

        <h2 class="text-2xl font-semibold text-neutral-800 dark:text-neutral-200 mt-8 mb-3">4. Data Privacy and Security</h2>
        <p class="text-neutral-600 dark:text-neutral-400 mb-4">
            We value your privacy. All personal and lease-related data are securely stored and used solely
            for the purpose of managing rental activities. We implement security measures to protect your
            information from unauthorized access.
        </p>

        <h2 class="text-2xl font-semibold text-neutral-800 dark:text-neutral-200 mt-8 mb-3">5. Maintenance Requests</h2>
        <p class="text-neutral-600 dark:text-neutral-400 mb-4">
            Tenants can submit maintenance requests through the system. Owners are responsible for addressing
            such requests promptly and updating the status accordingly.
        </p>

        <h2 class="text-2xl font-semibold text-neutral-800 dark:text-neutral-200 mt-8 mb-3">6. Termination of Accounts</h2>
        <p class="text-neutral-600 dark:text-neutral-400 mb-4">
            We reserve the right to suspend or terminate user accounts that violate these terms, engage in
            fraudulent activity, or misuse the platform.
        </p>

        <h2 class="text-2xl font-semibold text-neutral-800 dark:text-neutral-200 mt-8 mb-3">7. System Availability</h2>
        <p class="text-neutral-600 dark:text-neutral-400 mb-4">
            While we strive to maintain uninterrupted service, the system may experience downtime for
            maintenance or updates. We are not liable for any loss caused by system unavailability.
        </p>

        <h2 class="text-2xl font-semibold text-neutral-800 dark:text-neutral-200 mt-8 mb-3">8. Updates to These Terms</h2>
        <p class="text-neutral-600 dark:text-neutral-400 mb-4">
            We may revise these Terms and Conditions at any time. Continued use of the system
            after changes take effect constitutes acceptance of the new terms.
        </p>

        <h2 class="text-2xl font-semibold text-neutral-800 dark:text-neutral-200 mt-8 mb-3">9. Contact Information</h2>
        <p class="text-neutral-600 dark:text-neutral-400 mb-4">
            For any questions or concerns regarding these terms, please contact our support team at
            <a href="mailto:srpm.support@srpm.site" class="text-indigo-600 hover:underline">srpm.support@srpm.site</a>.
        </p>

        <p class="text-neutral-500 text-sm mt-12">
            © {{ date('Y') }} Smart Rental Property Management. All rights reserved.
        </p>
    </div>
</x-layouts.common>
