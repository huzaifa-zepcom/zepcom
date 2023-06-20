document.addEventListener('DOMContentLoaded', function() {
    const flipImageContainers = document.querySelectorAll('.flip-img-container');

    flipImageContainers.forEach(function(flipImageContainer) {
        const frontImage = flipImageContainer.querySelector('.flip-image-front');
        const backImage = flipImageContainer.querySelector('.flip-image-back');

        flipImageContainer.addEventListener('mouseenter', function () {
            frontImage.style.transform = 'rotateY(180deg)';
            backImage.style.transform = 'rotateY(0deg)';
        });

        flipImageContainer.addEventListener('mouseleave', function () {
            frontImage.style.transform = 'rotateY(0deg)';
            backImage.style.transform = 'rotateY(-180deg)';
        });
    });
});
