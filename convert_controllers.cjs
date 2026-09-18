const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(file => {
        const full = path.join(dir, file);
        if (fs.statSync(full).isDirectory()) {
            results = results.concat(walk(full));
        } else if (full.endsWith('.php')) {
            results.push(full);
        }
    });
    return results;
}

const controllers = walk('app/Http/Controllers');
for (const file of controllers) {
    let content = fs.readFileSync(file, 'utf8');
    if (content.includes('view(')) {
        if (!content.includes('use Inertia\\Inertia;')) {
            content = content.replace(/(use Illuminate\\Http\\Request;)/, "$1\nuse Inertia\\Inertia;\nuse Inertia\\Response as InertiaResponse;");
            if (!content.includes('use Inertia\\Inertia;')) {
                content = content.replace(/(namespace App\\Http\\Controllers.*?;)/, "$1\n\nuse Inertia\\Inertia;\nuse Inertia\\Response as InertiaResponse;");
            }
        }
        
        content = content.replace(/return view\('([^']+)'(?:,\s*(\[.*?\]|\$.*?))?\);/gs, (match, viewName, data) => {
            const inertiaName = viewName.split('.').map(p => p.charAt(0).toUpperCase() + p.slice(1)).join('/');
            if (data) {
                return "return Inertia::render('" + inertiaName + "', " + data + ");";
            }
            return "return Inertia::render('" + inertiaName + "');";
        });
        
        content = content.replace(/\\Illuminate\\View\\View/g, 'InertiaResponse');
        content = content.replace(/\bView\b/g, 'InertiaResponse');
        content = content.replace(/use Illuminate\\View\\InertiaResponse;\n/g, '');
        content = content.replace(/use Illuminate\\View\\View;\n/g, '');

        fs.writeFileSync(file, content);
        console.log('Updated ' + file);
    }
}
