module.exports = {
    apps: [
        {
            name: "matchfinance_laravel-queue",
            namespace: "default",
            script: "/usr/bin/php",
            args: "artisan queue:work --queue=ocr-processing,matching --tries=3",
            interpreter: "none",
            exec_mode: "fork_mode",
        },
    ],
};
