function showSection(id) {
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(sec => sec.classList.remove('active'));
    document.getElementById(id).classList.add('active');

    const lis = document.querySelectorAll('.submenu li');
    lis.forEach(li => li.classList.remove('active'));
    event.target.classList.add('active');
}

function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    
    const isVisible = submenu.style.display === 'block';
    
    submenu.style.display = isVisible ? 'none' : 'block';
    submenu.style.backgroundColor = isVisible ? '' : '#22223d';
    submenu.style.paddingLeft = isVisible ? '' : '15px';

}
