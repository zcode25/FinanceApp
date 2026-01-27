const fs = require('fs');
const path = require('path');

const filePath = path.join(process.cwd(), 'lang/en.json');

try {
    const rawData = fs.readFileSync(filePath, 'utf8');
    const data = JSON.parse(rawData);
    const cleanJson = JSON.stringify(data, null, 4);
    fs.writeFileSync(filePath, cleanJson);
    console.log('Fixed duplicates in ' + filePath);
} catch (error) {
    console.error('Error:', error.message);
}
