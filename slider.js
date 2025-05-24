document.addEventListener('DOMContentLoaded', function() {
    // Select all slide elements
    const slides = document.querySelectorAll('.slide');
    // Initialize current slide index
    let currentSlide = 0;

    // Function to display a specific slide
    function showSlide(index) {
        // Hide all slides by removing the 'active' class
        slides.forEach(slide => {
            slide.classList.remove('active');
        });

        // Check if the index is valid
        if (index >= 0 && index < slides.length) {
            // Show the slide at the given index by adding the 'active' class
            slides[index].classList.add('active');
            currentSlide = index; // Update currentSlide to the new index
        } else {
            // If index is out of bounds (should not happen with current logic, but good for robustness)
            // Default to showing the first slide
            slides[0].classList.add('active');
            currentSlide = 0;
        }
    }

    // Function to advance to the next slide
    function nextSlide() {
        currentSlide++; // Increment current slide index
        // If currentSlide goes beyond the last slide, reset to 0 (loop)
        if (currentSlide >= slides.length) {
            currentSlide = 0;
        }
        showSlide(currentSlide); // Display the new current slide
    }

    // Initial display: Show the first slide (index 0) when the page loads
    if (slides.length > 0) { // Ensure there are slides before trying to show them
        showSlide(0);

        // Auto-play: Call nextSlide every 5 seconds (5000 milliseconds)
        setInterval(nextSlide, 5000);
    } else {
        console.warn("Slider JS: No slides found with the class '.slide'.");
    }
});
