<?php

namespace RemoteTech\ComAxe\Client\Oauth\Model;

use Doctrine\DBAL\Types\Types;
//use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

//#[ORM\Entity]
class User implements UserInterface //, SerializerInterface
{
//    #[ORM\Id]
//    #[ORM\GeneratedValue]
//    #[ORM\Column]
    private ?int $id = null;

//    #[ORM\Column(length: 180, unique: true)]
    private ?string $uuid = null;

//    #[ORM\Column]
    private array $roles = [];

//    #[ORM\Column(type: Types::TEXT)]
    private ?string $token = null;

//    #[ORM\Column(type: Types::TEXT)]
    private ?string $refreshToken = null;

    private ?string $username = null;
    private ?string $email = null;
    private ?string $type = null;
    private ?string $status = null;
    private ?string $firstName = null;
    private ?string $lastName = null;

//    public function addTrustedSource(TrustedSource $trustedSource): static
//    {
//        if (!$this->trustedSources->contains($trustedSource)) {
//            $this->trustedSources->add($trustedSource);
//            $trustedSource->setCreatedBy($this);
//        }
//
//        return $this;
//    }
//
//    public function removeTrustedSource(TrustedSource $trustedSource): static
//    {
//        if ($this->trustedSources->removeElement($trustedSource)) {
//            // set the owning side to null (unless already changed)
//            if ($trustedSource->getCreatedBy() === $this) {
//                $trustedSource->setCreatedBy(null);
//            }
//        }
//
//        return $this;
//    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->uuid;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

//    public function serialize()
//    {
//        return serialize($this);
//    }
//
//
//    public function deserialize()
//    {
//        return unserialize();
//    }
}
