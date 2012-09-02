<?php
/*
TEMPLATE NAME: Add Project
*/

get_header();

?>
<h1>So, you want to submit a Project?</h1>
<h2>The Submission Process</h2>
<p>Our Project submission process is easy!  All you have to do is give us an idea of what your Project is and we will contact you to set it up.  We can even help get all of your content, goals, and rewards sorted out.  We don't make any judgement of which Projects get shown or funded so your dream is left up to you.</p>
<form name='submit-project' action='' method='post'>
    <label>Project Title</label>
    <input type='text' placeholder='enter your project title' style='width: 100%;' />

    <label>Short Description</label>
    <textarea placeholder='enter a short description of your project' style='width: 100%;' ></textarea>

    <label>Long Description</label>
    <textarea placeholder='enter a long description of your project' style='width: 100%;' ></textarea>

    <label>Goal Amount</label>
    <input type='text' placeholder='Goal Amount' style='width: 100%;'  />

    <label>Rewards</label>
    <textarea placeholder='describe your rewards, and their levels' style='width: 100%;' ></textarea>

    <button type='submit'>submit project</button>
</form>

<?php

get_footer();
?>
