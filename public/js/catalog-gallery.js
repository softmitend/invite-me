window.catalogGallery = function (element) {
    return {
        image: 0,
        images: JSON.parse(element.dataset.galleryImages || '[]'),
        timer: null,

        get total() {
            return this.images.length;
        },

        start() {
            if (this.total <= 1) {
                return;
            }

            this.timer = setInterval(() => {
                this.image = (this.image + 1) % this.total;
            }, 3500);
        },

        select(index) {
            this.image = index;

            if (this.timer) {
                clearInterval(this.timer);
                this.start();
            }
        }
    };
};
