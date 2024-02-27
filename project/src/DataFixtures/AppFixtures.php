<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        //création des clients
        for ($i = 1; $i < 3; $i++) {
            $client = new Client();
            $client->setName($i);
            $client->setEmail('client'.$i.'@apibilemo.com');
            $client->setPassword($this->userPasswordHasher->hashPassword($client, "password"));
            $client->setRoles(["ROLE_USER"]);
            $manager->persist($client);
            $clients[] = $client;
        }

        //création d'un admin
        $admin = new Client();
        $admin->setName('admin');
        $admin->setEmail('admin@apibilemo.com');
        $admin->setPassword($this->userPasswordHasher->hashPassword($admin, "password"));
        $admin->setRoles(["ROLE_ADMIN"]);
        $manager->persist($admin);

        //création des users
        for ($j = 0; $j < 20; $j++) {
            $user = new User();
            $user->setClient($clients[array_rand($clients)]);
            $user->setUsername('name'.$j);
            $manager->persist($user);
        }

        //création des produits
        for ($k = 0; $k < 20; $k++) {
            $product = new Product();
            $product->setTitle('title'.$k);
            $product->setPrice(rand(0,100));
            $product->setDescription('description'.$k);
            $product->setFeatures('features'.$k);
            $product->setText(text: 'text'.$k);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
