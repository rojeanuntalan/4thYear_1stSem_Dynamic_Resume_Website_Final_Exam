$(document).ready(function() {
    // Check if user is logged in
    checkAuth();
    
    // Load all content
    loadAbout();
    loadSkills();
    loadProjects();
    loadEducation();
    
    // Tab switching
    $('.tab-btn').click(function() {
        const section = $(this).data('section');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.section-content').removeClass('active');
        $('#' + section).addClass('active');
    });
    
    // Logout
    $('#logoutBtn, #logoutLink').click(function(e) {
        e.preventDefault();
        logout();
    });
    
    // Mobile toggle
    $("#mobile-toggle").on("click", function() {
        $("#nav-links").toggleClass("show");
    });
    
    // Modal controls
    $('.close-modal, .btn-secondary').click(function() {
        const modalId = $(this).data('modal');
        if (modalId) {
            $('#' + modalId).removeClass('show');
        }
    });
    
    $(window).click(function(event) {
        if ($(event.target).hasClass('modal')) {
            $(event.target).removeClass('show');
        }
    });
    
    // ABOUT HANDLERS
    $('#editAboutBtn').click(function() {
        loadAboutForEdit();
        $('#aboutModal').addClass('show');
    });
    
    $('#aboutForm').submit(function(e) {
        e.preventDefault();
        saveAbout();
    });
    
    // SKILLS HANDLERS
    $('#addSkillBtn').click(function() {
        resetSkillForm();
        $('#skillModal').addClass('show');
    });
    
    $('#skillForm').submit(function(e) {
        e.preventDefault();
        saveSkill();
    });
    
    // PROJECTS HANDLERS
    $('#addProjectBtn').click(function() {
        resetProjectForm();
        $('#projectModal').addClass('show');
    });
    
    $('#projectForm').submit(function(e) {
        e.preventDefault();
        saveProject();
    });
    
    // EDUCATION HANDLERS
    $('#addEducationBtn').click(function() {
        resetEducationForm();
        $('#educationModal').addClass('show');
    });
    
    $('#educationForm').submit(function(e) {
        e.preventDefault();
        saveEducation();
    });
    
    // File preview handlers
    // About image removed — no preview needed
    
    $('#projectImage').change(function() {
        previewImage(this, '#projectImagePreview');
    });
    
    $('#educationImage').change(function() {
        previewImage(this, '#educationImagePreview');
    });
});

function showMessage(message, type = 'error') {
    const $message = $('#message');
    $message.removeClass('success error').addClass(type);
    $message.text(message);
}

function checkAuth() {
    // Simple check - if they can load the page, they're logged in
    // Server handles session validation
}

function logout() {
    $.ajax({
        url: '../logout.php',
        method: 'GET',
        success: function() {
            window.location.href = 'index.html';
        }
    });
}

function previewImage(input, previewSelector) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $(previewSelector).html(`<img src="${e.target.result}" alt="Preview">`);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ABOUT FUNCTIONS
function loadAbout() {
    $.ajax({
        url: '../api/api_about.php?action=get',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.data) {
                const about = response.data;
                let html = '<h3>Current About</h3>';
                if (about.content) {
                    html += `<p>${about.content}</p>`;
                }
                if (about.interests) {
                    html += `<p><strong>Interests:</strong> ${about.interests}</p>`;
                }
                if (about.bucket_list) {
                    html += `<p><strong>Bucket List:</strong> ${about.bucket_list}</p>`;
                }
                if (about.motto) {
                    html += `<p><strong>Motto:</strong> ${about.motto}</p>`;
                }
                $('#aboutContent').html(html);
            } else {
                $('#aboutContent').html('<p>Click "Edit About" to add or update your about section.</p>');
            }
        },
        error: function() {
            $('#aboutContent').html('<p>Click "Edit About" to add or update your about section.</p>');
        }
    });
} 

function loadAboutForEdit() {
    $.ajax({
        url: '../api/api_about.php?action=get',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.data) {
                const about = response.data;
                $('#aboutContentInput').val(about.content || '');
                $('#aboutInterests').val(about.interests || '');
                $('#aboutBucketList').val(about.bucket_list || '');
                $('#aboutMotto').val(about.motto || '');
            } else {
                // clear form for new entry
                $('#aboutContentInput').val('');
                $('#aboutInterests').val('');
                $('#aboutBucketList').val('');
                $('#aboutMotto').val('');
            }
        }
    });
}

function saveAbout() {
    const formData = new FormData($('#aboutForm')[0]);
    formData.append('action', 'update');
    
    $.ajax({
        url: '../api/api_about.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                showMessage(response.message, 'success');
                $('#aboutModal').removeClass('show');
                loadAbout();
            } else {
                showMessage(response.message, 'error');
            }
        },
        error: function() {
            showMessage('An error occurred. Please try again.', 'error');
        }
    });
}

// SKILLS FUNCTIONS
function loadSkills() {
    $.ajax({
        url: '../api/api_skills.php?action=get_all',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const skills = response.data;
                if (skills.length === 0) {
                    $('#skillsList').html('<p>No skills added yet.</p>');
                    return;
                }
                
                let html = '';
                skills.forEach(skill => {
                    html += `
                        <div class="item-card">
                            <h3>${skill.skill_name}</h3>
                            <p><strong>Category:</strong> ${skill.category || 'N/A'}</p>
                            <p><strong>Proficiency:</strong> ${skill.proficiency}%</p>
                            <div style="background: #ddd; border-radius: 5px; height: 10px; overflow: hidden;">
                                <div style="background: var(--primary); height: 100%; width: ${skill.proficiency}%;"></div>
                            </div>
                            <div class="item-actions">
                                <button class="btn-edit" onclick="editSkill(${skill.id})">Edit</button>
                                <button class="btn-danger" onclick="deleteSkill(${skill.id})">Delete</button>
                            </div>
                        </div>
                    `;
                });
                $('#skillsList').html(html);
            }
        }
    });
}

function resetSkillForm() {
    $('#skillForm')[0].reset();
    $('#skillId').val('');
}

function editSkill(id) {
    $.ajax({
        url: '../api/api_skills.php?action=get&id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const skill = response.data;
                $('#skillId').val(skill.id);
                $('#skillName').val(skill.skill_name);
                $('#skillCategory').val(skill.category);
                $('#skillProficiency').val(skill.proficiency);
                $('#skillModal').addClass('show');
            }
        }
    });
}

function saveSkill() {
    const formData = new FormData($('#skillForm')[0]);
    const skillId = $('#skillId').val();
    formData.append('action', skillId ? 'update' : 'insert');
    
    $.ajax({
        url: '../api/api_skills.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                showMessage(response.message, 'success');
                $('#skillModal').removeClass('show');
                loadSkills();
            } else {
                showMessage(response.message, 'error');
            }
        },
        error: function() {
            showMessage('An error occurred. Please try again.', 'error');
        }
    });
}

function deleteSkill(id) {
    if (confirm('Are you sure you want to delete this skill?')) {
        $.ajax({
            url: '../api/api_skills.php',
            method: 'POST',
            data: { action: 'delete', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showMessage(response.message, 'success');
                    loadSkills();
                } else {
                    showMessage(response.message, 'error');
                }
            }
        });
    }
}

// PROJECTS FUNCTIONS
function loadProjects() {
    $.ajax({
        url: '../api/api_projects.php?action=get_all',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const projects = response.data;
                if (projects.length === 0) {
                    $('#projectsList').html('<p>No projects added yet.</p>');
                    return;
                }
                
                let html = '';
                projects.forEach(project => {
                    html += `
                        <div class="item-card">
                            ${project.image_path ? `<img src="../${project.image_path}" alt="${project.title}" style="width: 100%; border-radius: 5px; margin-bottom: 15px; max-height: 200px; object-fit: cover;">` : ''}
                            <h3>${project.title}</h3>
                            <p>${project.description}</p>
                            <p><strong>Technologies:</strong> ${project.technologies || 'N/A'}</p>
                            ${project.link ? `<p><a href="${project.link}" target="_blank" style="color: var(--accent);">View Project</a></p>` : ''}
                            <div class="item-actions">
                                <button class="btn-edit" onclick="editProject(${project.id})">Edit</button>
                                <button class="btn-danger" onclick="deleteProject(${project.id})">Delete</button>
                            </div>
                        </div>
                    `;
                });
                $('#projectsList').html(html);
            }
        }
    });
}

function resetProjectForm() {
    $('#projectForm')[0].reset();
    $('#projectId').val('');
    $('#projectImagePreview').html('');
}

function editProject(id) {
    $.ajax({
        url: '../api/api_projects.php?action=get&id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const project = response.data;
                $('#projectId').val(project.id);
                $('#projectTitle').val(project.title);
                $('#projectDescription').val(project.description);
                $('#projectTechs').val(project.technologies);
                $('#projectLink').val(project.link);
                if (project.image_path) {
                    $('#projectImagePreview').html(`<img src="../${project.image_path}" alt="Preview">`);
                }
                $('#projectModal').addClass('show');
            }
        }
    });
}

function saveProject() {
    const formData = new FormData($('#projectForm')[0]);
    const projectId = $('#projectId').val();
    formData.append('action', projectId ? 'update' : 'insert');
    
    $.ajax({
        url: '../api/api_projects.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                showMessage(response.message, 'success');
                $('#projectModal').removeClass('show');
                loadProjects();
            } else {
                showMessage(response.message, 'error');
            }
        },
        error: function() {
            showMessage('An error occurred. Please try again.', 'error');
        }
    });
}

function deleteProject(id) {
    if (confirm('Are you sure you want to delete this project?')) {
        $.ajax({
            url: '../api/api_projects.php',
            method: 'POST',
            data: { action: 'delete', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showMessage(response.message, 'success');
                    loadProjects();
                } else {
                    showMessage(response.message, 'error');
                }
            }
        });
    }
}

// EDUCATION FUNCTIONS
function loadEducation() {
    $.ajax({
        url: '../api/api_education.php?action=get_all',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const education = response.data;
                if (education.length === 0) {
                    $('#educationList').html('<p>No education records added yet.</p>');
                    return;
                }
                
                let html = '';
                education.forEach(edu => {
                    const years = edu.start_year && edu.end_year ? `${edu.start_year} - ${edu.end_year}` : '';
                    html += `
                        <div class="item-card">
                            ${edu.image_path ? `<img src="../${edu.image_path}" alt="${edu.institution}" style="width: 100%; border-radius: 5px; margin-bottom: 15px; max-height: 150px; object-fit: contain;">` : ''}
                            <h3>${edu.degree}</h3>
                            <p><strong>${edu.institution}</strong></p>
                            ${edu.field ? `<p><strong>Field:</strong> ${edu.field}</p>` : ''}
                            ${years ? `<p><strong>Years:</strong> ${years}</p>` : ''}
                            <div class="item-actions">
                                <button class="btn-edit" onclick="editEducation(${edu.id})">Edit</button>
                                <button class="btn-danger" onclick="deleteEducation(${edu.id})">Delete</button>
                            </div>
                        </div>
                    `;
                });
                $('#educationList').html(html);
            }
        }
    });
}

function resetEducationForm() {
    $('#educationForm')[0].reset();
    $('#educationId').val('');
    $('#educationImagePreview').html('');
}

function editEducation(id) {
    $.ajax({
        url: '../api/api_education.php?action=get&id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const edu = response.data;
                $('#educationId').val(edu.id);
                $('#educationInstitution').val(edu.institution);
                $('#educationDegree').val(edu.degree);
                $('#educationField').val(edu.field);
                $('#educationStartYear').val(edu.start_year);
                $('#educationEndYear').val(edu.end_year);
                if (edu.image_path) {
                    $('#educationImagePreview').html(`<img src="../${edu.image_path}" alt="Preview">`);
                }
                $('#educationModal').addClass('show');
            }
        }
    });
}

function saveEducation() {
    const formData = new FormData($('#educationForm')[0]);
    const educationId = $('#educationId').val();
    formData.append('action', educationId ? 'update' : 'insert');
    
    $.ajax({
        url: '../api/api_education.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                showMessage(response.message, 'success');
                $('#educationModal').removeClass('show');
                loadEducation();
            } else {
                showMessage(response.message, 'error');
            }
        },
        error: function() {
            showMessage('An error occurred. Please try again.', 'error');
        }
    });
}

function deleteEducation(id) {
    if (confirm('Are you sure you want to delete this education record?')) {
        $.ajax({
            url: '../api/api_education.php',
            method: 'POST',
            data: { action: 'delete', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showMessage(response.message, 'success');
                    loadEducation();
                } else {
                    showMessage(response.message, 'error');
                }
            }
        });
    }
}
