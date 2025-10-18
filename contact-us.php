<?php
include('header.php');
?>

<div class="container mx-auto px-4 py-8 md:py-16">

    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold">Contact Us</h1>
        <p class="mt-2 text-lg theme-muted">We'd love to hear from you. Here's how you can reach us.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
        <div class="theme-card p-6 rounded-lg text-center">
            <span class="material-symbols-outlined text-4xl mx-auto" style="color:var(--primary-color);">location_on</span>
            <h3 class="text-xl font-bold mt-4">Our Address</h3>
            <p class="mt-1 theme-muted">Jila Parishad, Saharsa<br>Bihar - 852202</p>
        </div>
        <div class="theme-card p-6 rounded-lg text-center">
            <span class="material-symbols-outlined text-4xl mx-auto" style="color:var(--primary-color);">email</span>
            <h3 class="text-xl font-bold mt-4">Email Us</h3>
            <p class="mt-1 theme-muted break-words">support@servedoor.com</p>
        </div>
        <div class="theme-card p-6 rounded-lg text-center">
            <span class="material-symbols-outlined text-4xl mx-auto" style="color:var(--primary-color);">call</span>
            <h3 class="text-xl font-bold mt-4">Call Us</h3>
            <p class="mt-1 theme-muted">+91 6205411077</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 theme-card p-6 sm:p-8 rounded-lg shadow-xl">
        <div>
            <h2 class="text-2xl font-bold mb-4">Get In Touch</h2>
            <form action="contact_us_submit.php" method="post" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium theme-muted mb-1">Full Name</label>
                        <input type="text" name="name" class="theme-input" required>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium theme-muted mb-1">Email Address</label>
                        <input type="email" name="email" class="theme-input" required>
                    </div>
                </div>
                <div>
                    <label for="mobile" class="block text-sm font-medium theme-muted mb-1">Mobile</label>
                    <input type="text" name="mobile" class="theme-input" required>
                </div>
                <div>
                    <label for="subject" class="block text-sm font-medium theme-muted mb-1">Subject</label>
                    <input type="text" name="subject" class="theme-input" required>
                </div>
                <div>
                    <label for="message" class="block text-sm font-medium theme-muted mb-1">Message</label>
                    <textarea name="message" rows="4" class="theme-input" required></textarea>
                </div>
                <div>
                    <button type="submit" class="w-full text-white font-bold py-3 px-6 rounded-lg text-lg" style="background:var(--primary-color)">
                        Send Message
                    </button>
                </div>
            </form>
        </div>

        <div>
            <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3584.821161281781!2d86.5954663742415!3d25.88956990146036!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39ee4d3522ffffff%3A0x6bf839498e72c385!2sJila%20Parishad!5e0!3m2!1sen!2sin!4v1726702635955!5m2!1sen!2sin" 
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy"
                class="rounded-lg">
            </iframe>
        </div>
    </div>
</div>

<?php
include('footer.php');
?>