(() => {
    const script = document.currentScript;
    const stylesheet = script.dataset.stylesheet;
    const letterhead = script.dataset.letterhead;
    window.ReportPrint = {
        async printHtml(html) {
            const report = new DOMParser().parseFromString(html, 'text/html');
            report.querySelectorAll('.report-print-letterhead').forEach(image => image.remove());
            const link = report.createElement('link');
            link.rel = 'stylesheet';
            link.href = stylesheet;
            report.head.append(link);
            report.body.classList.add('report-print-document');
            const content = report.createElement('main');
            content.className = 'report-print-content';
            content.append(...Array.from(report.body.childNodes));
            const image = report.createElement('img');
            image.className = 'report-print-letterhead';
            image.src = letterhead;
            image.alt = 'PHILCST header and footer';
            const layout = report.createElement('table');
            layout.className = 'report-page-layout';
            const header = report.createElement('thead');
            header.innerHTML = '<tr><td><div class="report-header-space"></div></td></tr>';
            const footer = report.createElement('tfoot');
            footer.innerHTML = '<tr><td><div class="report-footer-space"></div></td></tr>';
            const body = report.createElement('tbody');
            const row = report.createElement('tr');
            const cell = report.createElement('td');
            cell.append(content);
            row.append(cell);
            body.append(row);
            layout.append(header, body, footer);
            report.body.append(image, layout);
            const url = URL.createObjectURL(new Blob(['<!DOCTYPE html>' + report.documentElement.outerHTML], { type: 'text/html' }));
            const frame = document.createElement('iframe');
            frame.title = 'Print report';
            frame.style.cssText = 'position:fixed;width:0;height:0;border:0;visibility:hidden';
            const cleanup = () => { frame.remove(); URL.revokeObjectURL(url); };
            frame.onload = async () => {
                try {
                    await Promise.all(Array.from(frame.contentDocument.images, img => img.decode()));
                    await frame.contentDocument.fonts.ready;
                    frame.contentWindow.onafterprint = cleanup;
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } catch (error) {
                    cleanup();
                    window.alert('The report header and footer could not load. Please refresh and try again.');
                }
            };
            frame.src = url;
            document.body.append(frame);
        },
    };
})();
