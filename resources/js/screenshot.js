import puppeteer from 'puppeteer-core';

async function takeScreenshot(htmlPath, outputPath) {
    const browser = await puppeteer.launch({
        headless: 'new',
        executablePath: '/usr/bin/chromium',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 300, height: 800 });
        await page.goto(`file://${htmlPath}`, { waitUntil: 'networkidle0' });
        await page.screenshot({ 
            path: outputPath,
            fullPage: true
        });
    } finally {
        await browser.close();
    }
}

// Get command line arguments
const htmlPath = process.argv[2];
const outputPath = process.argv[3];

if (!htmlPath || !outputPath) {
    console.error('Usage: node screenshot.js <html-path> <output-path>');
    process.exit(1);
}

takeScreenshot(htmlPath, outputPath)
    .catch(err => {
        console.error(err);
        process.exit(1);
    });
