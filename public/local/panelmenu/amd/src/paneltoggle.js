define([], function () {
    return {
        init: function () {           
            const trigger = document.querySelector('.custom-panel-trigger');
            const sidebar = document.getElementById('custom-panel-sidebar');
            const closebtn = sidebar?.querySelector('.closebtn');
            const backbtn = sidebar?.querySelector('.backbtn');
            const menuColumn = sidebar?.querySelector('.menu-column');
            const contentColumn = sidebar?.querySelector('.content-column');
            const menuItems = sidebar?.querySelectorAll('.menu-item');
            const submenus = sidebar?.querySelectorAll('.submenu-content');

            // ✅ Inject custom CSS
            const style = document.createElement('style');
            style.innerHTML = `
                .custom-sidebar.open {
                    width: calc(100% - 80px);
                    margin-top: 76px;
                    max-height: 70%;
                    padding-left: 0px;
                    padding-top: 0px;
                    border-radius: 0px 0px 10px 10px;
                     margin-left:40px;
                }

                /* Show + highlight arrow when active */
                .menu-column .menu-item.active i {
                    display: inline-block !important;
                    
                }
                     
                @media (max-width: 768px) {
                    .custom-sidebar.open {
                        padding-left: 0px; 
                    }
                    .sidebar-content {
                    
                    width: 100%;
                    }
                }

                /* Mobile layout overrides */
                @media (max-width: 450px) {
                    .menu-column { width: 100% !important; }
                    .content-column { width: 100% !important; display: none; }
                    .content-column.active { display: block; }
                    .menu-column.hidden { display: none; }
                    .backbtn { display: none !important; font-weight: 600; align-items: center; }
                    .backbtn.show { display: flex !important; gap: 6px; }
                    .backbtn i { font-size: 14px; }
                }
            `;
            document.head.appendChild(style);

            // Toggle sidebar open
            if (trigger) {
                trigger.addEventListener('click', function (e) {
                    e.preventDefault();
                    sidebar.classList.add('open');
                });
            }

            // Toggle sidebar close
            if (closebtn) {
                closebtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    sidebar.classList.remove('open');
                });
            }

            // ✅ Mobile: hide content-column by default
            if (window.innerWidth <= 450 && contentColumn) {
                contentColumn.style.display = 'none';
            }

            // ✅ Handle menu clicks
            if (menuItems) {
                menuItems.forEach(item => {
                    item.addEventListener('click', function (e) {
                        e.preventDefault();

                        // reset active states
                        menuItems.forEach(i => i.classList.remove('active'));
                        submenus.forEach(c => c.classList.add('d-none'));

                        // activate clicked item + submenu
                        this.classList.add('active');
                        const target = this.getAttribute('data-target');
                        document.getElementById(target)?.classList.remove('d-none');

                        // mobile: switch to content column
                        if (window.innerWidth <= 450) {
                            menuColumn?.classList.add('hidden');
                            contentColumn?.classList.add('active');
                            contentColumn.style.display = 'block';

                            // ✅ show back button with FA left arrow + current menu label
                            const label = this.querySelector("span")?.textContent.trim() || this.textContent.trim();
                            if (backbtn) {
                                backbtn.innerHTML = `<i class="fa-solid fa-chevron-left mr-2"></i> ${label}`;
                                backbtn.classList.add('show');
                            }
                        }
                    });
                });
            }

            // ✅ Back button logic
            if (backbtn) {
                backbtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (window.innerWidth <= 450) {
                        menuColumn?.classList.remove('hidden');
                        contentColumn?.classList.remove('active');
                        contentColumn.style.display = 'none';

                        // reset back button
                        backbtn.classList.remove('show');
                        backbtn.innerHTML = "";
                    }
                });
            }

            // Auto-open first menu (desktop only)
            if (window.innerWidth > 450) {
                const firstItem = document.querySelector('.menu-item');
                if (firstItem) {
                    firstItem.click();
                }
            }
        }
    };
});
