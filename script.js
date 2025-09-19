gsap.registerPlugin(ScrollTrigger);

const logo = document.querySelector('#logo-animation');
const slogan = document.querySelector('.text-center h1');

// Animation du logo et du slogan
if (logo && slogan) {
    const mainTimeline = gsap.timeline({ defaults: { ease: "power2.out" } });
    mainTimeline
        .from(logo, { duration: 1, opacity: 0, scale: 0.5 })
        .from(slogan, { duration: 1.5, opacity: 0, y: -50 }, "-=0.5");

    mainTimeline.eventCallback("onComplete", () => {
        gsap.to("#logo-animation", {
            duration: 5,
            rotation: 360,
            ease: "none",
            repeat: -1,
            transformOrigin: "50% 50%"
        });
    });
}
// Animation des images avec ScrollTrigger
let mm = gsap.matchMedia();

mm.add("(min-width: 768px)", () => {
    gsap.timeline({
        scrollTrigger: { trigger: ".image-gallery", start: "top 80%" }
    })
        .from(".image-gallery .col-md-4:nth-child(1) img", { opacity: 0, x: -100, duration: 1 })
        .from(".image-gallery .col-md-4:nth-child(2) img", { opacity: 0, y: 50, duration: 1 }, "-=0.5")
        .from(".image-gallery .col-md-4:nth-child(3) img", { opacity: 0, x: 100, duration: 1 }, "-=0.5");
});

mm.add("(max-width: 767px)", () => {
    gsap.timeline({
        scrollTrigger: { trigger: ".image-gallery", start: "top 80%" }
    })
        .from(".image-gallery .col-md-4:nth-child(1) img", { opacity: 0, x: -100, duration: 1 })
        .from(".image-gallery .col-md-4:nth-child(2) img", { opacity: 0, x: 100, duration: 1 }, "-=0.5")
        .from(".image-gallery .col-md-4:nth-child(3) img", { opacity: 0, x: -100, duration: 1 }, "-=0.5");
});
// Animation de la section de recherche
gsap.from(".input-group", {
    duration: 1.5,
    y: 50,
    opacity: 0,
    ease: "power2.out",
    scrollTrigger: {
        trigger: ".input-group",
        start: "top 90%",
        toggleActions: "play none none none"
    }
});