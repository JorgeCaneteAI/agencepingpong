/* ========================================
   SCENE3D.JS — Three.js raquette ping pong
   ======================================== */

(function () {
    'use strict';

    // --- Config ---
    const CONFIG = {
        canvasId: 'canvas-3d',
        modelPath: 'assets/models/raquette.glb',
        cameraFov: 45,
        cameraNear: 0.1,
        cameraFar: 100,
        cameraZ: 5,
        ambientLightColor: 0xffedd7,
        ambientLightIntensity: 0.4,
        directionalLightColor: 0xffffff,
        directionalLightIntensity: 0.8,
        directionalLightPosition: { x: 5, y: 5, z: 5 },
        ballRadius: 0.08,
        ballColor: 0xffedd7,
    };

    let canvas, renderer, scene, camera;
    let raquette = null;
    let ball = null;
    let scrollProgress = 0;
    let isReady = false;

    window.addEventListener('load', init);

    function init() {
        canvas = document.getElementById(CONFIG.canvasId);
        if (!canvas) return;

        // --- Renderer ---
        renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            antialias: true,
            alpha: true,
        });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.outputEncoding = THREE.sRGBEncoding;

        // --- Scene ---
        scene = new THREE.Scene();

        // --- Camera ---
        camera = new THREE.PerspectiveCamera(
            CONFIG.cameraFov,
            window.innerWidth / window.innerHeight,
            CONFIG.cameraNear,
            CONFIG.cameraFar
        );
        camera.position.z = CONFIG.cameraZ;

        // --- Lights ---
        const ambientLight = new THREE.AmbientLight(
            CONFIG.ambientLightColor,
            CONFIG.ambientLightIntensity
        );
        scene.add(ambientLight);

        const directionalLight = new THREE.DirectionalLight(
            CONFIG.directionalLightColor,
            CONFIG.directionalLightIntensity
        );
        directionalLight.position.set(
            CONFIG.directionalLightPosition.x,
            CONFIG.directionalLightPosition.y,
            CONFIG.directionalLightPosition.z
        );
        scene.add(directionalLight);

        // --- Ball (placeholder sphere) ---
        const ballGeometry = new THREE.SphereGeometry(CONFIG.ballRadius, 32, 32);
        const ballMaterial = new THREE.MeshStandardMaterial({ color: CONFIG.ballColor });
        ball = new THREE.Mesh(ballGeometry, ballMaterial);
        ball.position.set(2, 0, 0);
        ball.visible = false;
        scene.add(ball);

        // --- Load raquette model ---
        loadModel();

        // --- Scroll tracking ---
        setupScrollTracking();

        // --- Resize ---
        window.addEventListener('resize', onResize);

        // --- Render loop ---
        animate();
    }

    function loadModel() {
        var loader = new THREE.GLTFLoader();

        loader.load(
            CONFIG.modelPath,
            function(gltf) {
                raquette = gltf.scene;
                raquette.scale.set(1, 1, 1);
                raquette.position.set(0, 0, 0);
                scene.add(raquette);
                isReady = true;
            },
            undefined,
            function(error) {
                console.warn('Modèle 3D non trouvé, utilisation du placeholder');
                createPlaceholderRaquette();
            }
        );
    }

    function createPlaceholderRaquette() {
        // Raquette simplifiée en géométrie basique
        var group = new THREE.Group();

        // Tampon (cylindre aplati)
        var padGeometry = new THREE.CylinderGeometry(0.7, 0.7, 0.05, 32);
        var padMaterial = new THREE.MeshStandardMaterial({ color: 0xdc5000 });
        var pad = new THREE.Mesh(padGeometry, padMaterial);
        pad.rotation.x = Math.PI / 2;
        group.add(pad);

        // Manche (cylindre fin)
        var handleGeometry = new THREE.CylinderGeometry(0.08, 0.08, 0.8, 16);
        var handleMaterial = new THREE.MeshStandardMaterial({ color: 0x382416 });
        var handle = new THREE.Mesh(handleGeometry, handleMaterial);
        handle.position.y = -0.9;
        group.add(handle);

        raquette = group;
        scene.add(raquette);
        isReady = true;
    }

    function setupScrollTracking() {
        // Track overall scroll progress (0 to 1)
        ScrollTrigger.create({
            trigger: document.body,
            start: 'top top',
            end: 'bottom bottom',
            onUpdate: function(self) {
                scrollProgress = self.progress;
            },
        });

        // --- Hero section: raquette face caméra, balle frappe ---
        ScrollTrigger.create({
            trigger: '#hero',
            start: 'top top',
            end: 'bottom top',
            onUpdate: function(self) {
                if (!isReady || !raquette) return;
                var p = self.progress;

                // Raquette: rotation légère
                raquette.rotation.y = p * Math.PI * 0.5;
                raquette.rotation.x = Math.sin(p * Math.PI) * 0.3;
                raquette.position.x = p * -1.5;

                // Balle: apparaît et traverse
                if (p > 0.3 && p < 0.8) {
                    ball.visible = true;
                    var ballProgress = (p - 0.3) / 0.5;
                    ball.position.x = -2 + ballProgress * 6;
                    ball.position.y = Math.sin(ballProgress * Math.PI) * 1.5;
                } else {
                    ball.visible = false;
                }
            },
        });

        // --- Concept section: rotation lente flottante ---
        ScrollTrigger.create({
            trigger: '#concept',
            start: 'top bottom',
            end: 'bottom top',
            onUpdate: function(self) {
                if (!isReady || !raquette) return;
                var p = self.progress;
                raquette.rotation.y = Math.PI * 0.5 + p * Math.PI * 0.3;
                raquette.rotation.z = Math.sin(p * Math.PI * 2) * 0.1;
                raquette.position.x = -1.5 + Math.sin(p * Math.PI) * 0.5;
                raquette.position.y = Math.sin(p * Math.PI * 2) * 0.2;
                raquette.scale.setScalar(1 - p * 0.1);
            },
        });

        // --- Services section: inclinée, balle rebondit ---
        ScrollTrigger.create({
            trigger: '#services',
            start: 'top bottom',
            end: 'bottom top',
            onUpdate: function(self) {
                if (!isReady || !raquette) return;
                var p = self.progress;
                raquette.rotation.y = Math.PI * 0.8 + p * 0.2;
                raquette.rotation.x = 0.3;
                raquette.position.x = 2 - p * 0.5;
                raquette.position.y = -0.5 + p * 0.5;
                raquette.scale.setScalar(0.9);

                // Balle rebondissante
                ball.visible = true;
                ball.position.x = Math.sin(p * Math.PI * 3) * 1.5;
                ball.position.y = Math.abs(Math.sin(p * Math.PI * 4)) * 1;
                ball.position.z = 1;
            },
        });

        // --- Réalisations: en retrait ---
        ScrollTrigger.create({
            trigger: '#realisations',
            start: 'top bottom',
            end: 'bottom top',
            onUpdate: function(self) {
                if (!isReady || !raquette) return;
                raquette.position.z = -2;
                raquette.position.x = 3;
                raquette.scale.setScalar(0.5);
                raquette.rotation.y += 0.001;
                ball.visible = false;
            },
        });

        // --- Contact: revient au centre ---
        ScrollTrigger.create({
            trigger: '#contact',
            start: 'top bottom',
            end: 'bottom top',
            onUpdate: function(self) {
                if (!isReady || !raquette) return;
                var p = self.progress;
                raquette.position.x = 3 - p * 3;
                raquette.position.y = 0;
                raquette.position.z = -2 + p * 2;
                raquette.scale.setScalar(0.5 + p * 0.5);
                raquette.rotation.y = Math.PI + p * Math.PI;

                // Balle revient
                if (p > 0.5) {
                    ball.visible = true;
                    var bp = (p - 0.5) / 0.5;
                    ball.position.x = 3 - bp * 3;
                    ball.position.y = Math.sin(bp * Math.PI) * 0.8;
                    ball.position.z = 0;
                }
            },
        });
    }

    function onResize() {
        if (!camera || !renderer) return;
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    }

    function animate() {
        requestAnimationFrame(animate);
        if (renderer && scene && camera) {
            renderer.render(scene, camera);
        }
    }
})();
