To get the assignment working on your local environment:

1. git clone git@github.com:semogilevsky/random_cat.git d9-random-cat
2. cd d9-random-cat
3. ddev start
4. ddev composer create "drupal/recommended-project"
5. ddev composer require drush/drush
6. ddev drush site:install -y
7. cp -r .ddev/drupal-customs/random_cat web/modules/random_cat
8. ddev drush en random_cat -y && ddev drush cr
9. ddev launch
10. On the frontpage bootm there should be two widgets with cats
